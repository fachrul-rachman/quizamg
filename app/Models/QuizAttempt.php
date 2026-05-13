<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class QuizAttempt extends Model
{
    protected $fillable = [
        'quiz_link_id',
        'quiz_id',
        'participant_name',
        'participant_applied_for',
        'started_at',
        'submitted_at',
        'time_limit_minutes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'submitted_at' => 'datetime',
            'time_limit_minutes' => 'integer',
        ];
    }

    public function quizLink(): BelongsTo
    {
        return $this->belongsTo(QuizLink::class, 'quiz_link_id');
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(AttemptAnswer::class, 'quiz_attempt_id');
    }

    public function result(): HasOne
    {
        return $this->hasOne(QuizResult::class, 'quiz_attempt_id');
    }
}
