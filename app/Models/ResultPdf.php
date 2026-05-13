<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResultPdf extends Model
{
    protected $table = 'result_pdfs';

    protected $fillable = [
        'quiz_result_id',
        'file_name',
        'local_path',
        'google_drive_file_id',
        'google_drive_url',
        'generated_at',
        'uploaded_at',
    ];

    protected function casts(): array
    {
        return [
            'generated_at' => 'datetime',
            'uploaded_at' => 'datetime',
        ];
    }

    public function result(): BelongsTo
    {
        return $this->belongsTo(QuizResult::class, 'quiz_result_id');
    }
}

