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
        'participant_age',
        'participant_height_cm',
        'participant_weight_kg',
        'participant_last_job',
        'participant_last_company',
        'participant_last_job_started_at',
        'participant_current_domicile',
        'started_at',
        'submitted_at',
        'time_limit_minutes',
        'quiz_snapshot',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'submitted_at' => 'datetime',
            'time_limit_minutes' => 'integer',
            'participant_age' => 'integer',
            'participant_height_cm' => 'decimal:2',
            'participant_weight_kg' => 'decimal:2',
            'participant_last_job_started_at' => 'date',
            'quiz_snapshot' => 'array',
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
