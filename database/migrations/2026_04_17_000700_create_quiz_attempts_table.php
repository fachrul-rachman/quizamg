<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_link_id')->unique()->constrained('quiz_links');
            $table->foreignId('quiz_id')->constrained('quizzes');
            $table->string('participant_name', 255);
            $table->string('participant_ktp_number', 50);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->unsignedInteger('time_limit_minutes');
            $table->enum('status', ['not_started', 'in_progress', 'submitted', 'auto_submitted'])->default('not_started');
            $table->timestamps();

            $table->index('quiz_id');
            $table->index('status');
            $table->index('participant_name');
            $table->index('participant_ktp_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_attempts');
    }
};

