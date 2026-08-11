<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table): void {
            $table->string('participant_cv_email')
                ->nullable()
                ->after('participant_applied_for');
        });
    }

    public function down(): void
    {
        // Intentionally irreversible to prevent participant data removal.
    }
};
