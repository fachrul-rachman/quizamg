<?php

namespace App\Support;

class DeterministicShuffle
{
    /**
     * @template T
     * @param  array<int, T>  $items
     * @return array<int, T>
     */
    public static function shuffle(array $items, int $seed): array
    {
        $items = array_values($items);
        $n = count($items);

        $state = self::normalizeSeed($seed);

        for ($i = $n - 1; $i > 0; $i--) {
            $state = self::xorshift32($state);
            $j = $state % ($i + 1);

            $tmp = $items[$i];
            $items[$i] = $items[$j];
            $items[$j] = $tmp;
        }

        return $items;
    }

    private static function normalizeSeed(int $seed): int
    {
        $seed = $seed & 0xFFFFFFFF;
        return $seed === 0 ? 0x6D2B79F5 : $seed;
    }

    private static function xorshift32(int $state): int
    {
        $x = $state & 0xFFFFFFFF;
        $x ^= (($x << 13) & 0xFFFFFFFF);
        $x ^= ($x >> 17);
        $x ^= (($x << 5) & 0xFFFFFFFF);
        return $x & 0xFFFFFFFF;
    }
}

