<?php

namespace App\Services\Xlsx;

use DOMDocument;
use DOMXPath;
use RuntimeException;
use ZipArchive;

class SimpleXlsxReader
{
    /**
     * @return array<int, array<string, string>>
     */
    public function readFirstWorksheetRows(string $xlsxPath): array
    {
        $zip = new ZipArchive();
        $opened = $zip->open($xlsxPath);
        if ($opened !== true) {
            throw new RuntimeException('File import tidak valid.');
        }

        try {
            $sharedStrings = $this->readSharedStrings($zip);
            $worksheetXmlPath = $this->findFirstWorksheetXmlPath($zip);
            $xml = $zip->getFromName($worksheetXmlPath);
            if ($xml === false) {
                throw new RuntimeException('File import tidak valid.');
            }

            return $this->parseWorksheetRows($xml, $sharedStrings);
        } finally {
            $zip->close();
        }
    }

    /**
     * @return array<int, string>
     */
    private function readSharedStrings(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');
        if ($xml === false) {
            return [];
        }

        $doc = new DOMDocument();
        $doc->loadXML($xml, LIBXML_NONET);
        $xpath = new DOMXPath($doc);
        $xpath->registerNamespace('s', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

        $strings = [];
        foreach ($xpath->query('//s:sst/s:si') as $si) {
            $textNodes = $xpath->query('.//s:t', $si);
            $parts = [];
            foreach ($textNodes as $t) {
                $parts[] = $t->textContent;
            }
            $strings[] = implode('', $parts);
        }

        return $strings;
    }

    private function findFirstWorksheetXmlPath(ZipArchive $zip): string
    {
        $candidates = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            if (! is_array($stat) || ! isset($stat['name'])) {
                continue;
            }
            $name = (string) $stat['name'];
            if (preg_match('#^xl/worksheets/sheet(\d+)\.xml$#', $name, $m)) {
                $candidates[(int) $m[1]] = $name;
            }
        }

        if ($candidates === []) {
            throw new RuntimeException('File import tidak valid.');
        }

        ksort($candidates);
        return array_values($candidates)[0];
    }

    /**
     * @param  array<int, string>  $sharedStrings
     * @return array<int, array<string, string>>
     */
    private function parseWorksheetRows(string $worksheetXml, array $sharedStrings): array
    {
        $doc = new DOMDocument();
        $doc->loadXML($worksheetXml, LIBXML_NONET);
        $xpath = new DOMXPath($doc);
        $xpath->registerNamespace('s', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

        $rows = [];
        foreach ($xpath->query('//s:worksheet/s:sheetData/s:row') as $rowEl) {
            $row = [];
            foreach ($xpath->query('./s:c', $rowEl) as $cellEl) {
                $ref = $cellEl->attributes?->getNamedItem('r')?->nodeValue ?? '';
                if (! preg_match('/^([A-Z]+)\d+$/', $ref, $m)) {
                    continue;
                }
                $col = $m[1];

                $type = $cellEl->attributes?->getNamedItem('t')?->nodeValue ?? null;
                $value = '';

                if ($type === 'inlineStr') {
                    $t = $xpath->query('.//s:is/s:t', $cellEl)->item(0);
                    $value = $t?->textContent ?? '';
                } else {
                    $v = $xpath->query('./s:v', $cellEl)->item(0);
                    $raw = $v?->textContent ?? '';

                    if ($type === 's') {
                        $idx = (int) $raw;
                        $value = $sharedStrings[$idx] ?? '';
                    } else {
                        $value = $raw;
                    }
                }

                $row[$col] = $value;
            }

            if ($row !== []) {
                $rows[] = $row;
            }
        }

        return $rows;
    }
}

