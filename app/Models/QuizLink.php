<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class QuizLink extends Model
{
    protected $fillable = [
        'quiz_id',
        'token',
        'usage_type',
        'status',
        'opened_at',
        'started_at',
        'submitted_at',
        'expired_at',
        'expires_at',
        'google_drive_folder_id',
        'google_drive_folder_url',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'opened_at' => 'datetime',
            'started_at' => 'datetime',
            'submitted_at' => 'datetime',
            'expired_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function attempt(): HasOne
    {
        return $this->hasOne(QuizAttempt::class, 'quiz_link_id')->latestOfMany();
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class, 'quiz_link_id');
    }
}
