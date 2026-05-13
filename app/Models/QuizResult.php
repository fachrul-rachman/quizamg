<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizResult extends Model
{
    protected $fillable = [
        'quiz_attempt_id',
        'quiz_id',
        'total_questions',
        'correct_answers',
        'wrong_answers',
        'unanswered_answers',
        'score_percentage',
        'grade_letter',
        'grade_label',
        'result_status',
        'calculated_at',
    ];

    protected function casts(): array
    {
        return [
            'total_questions' => 'integer',
            'correct_answers' => 'integer',
            'wrong_answers' => 'integer',
            'unanswered_answers' => 'integer',
            'score_percentage' => 'decimal:2',
            'calculated_at' => 'datetime',
        ];
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(QuizAttempt::class, 'quiz_attempt_id');
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }
}

