<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quiz extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'duration_minutes',
        'shuffle_questions',
        'shuffle_options',
        'instant_feedback_enabled',
        'difficulty_levels_enabled',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'duration_minutes' => 'integer',
            'shuffle_questions' => 'boolean',
            'shuffle_options' => 'boolean',
            'instant_feedback_enabled' => 'boolean',
            'difficulty_levels_enabled' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function gradeRules(): HasMany
    {
        return $this->hasMany(QuizGradeRule::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
