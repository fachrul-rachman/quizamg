<?php

namespace App\Services;

class GradeService
{
    /**
     * @return array{grade_letter:string, grade_label:string}
     */
    public function fromScorePercentage(float $scorePercentage): array
    {
        $scorePercentage = max(0.0, min(100.0, $scorePercentage));

        if ($scorePercentage >= 80.0) {
            return ['grade_letter' => 'A', 'grade_label' => 'Sangat Baik'];
        }

        if ($scorePercentage >= 65.0) {
            return ['grade_letter' => 'B', 'grade_label' => 'Baik'];
        }

        if ($scorePercentage >= 50.0) {
            return ['grade_letter' => 'C', 'grade_label' => 'Cukup'];
        }

        if ($scorePercentage >= 35.0) {
            return ['grade_letter' => 'D', 'grade_label' => 'Kurang'];
        }

        return ['grade_letter' => 'E', 'grade_label' => 'Sangat Kurang'];
    }
}
