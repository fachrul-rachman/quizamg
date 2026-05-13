<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_attempt_id')->unique()->constrained('quiz_attempts');
            $table->foreignId('quiz_id')->constrained('quizzes');
            $table->unsignedInteger('total_questions');
            $table->unsignedInteger('correct_answers');
            $table->unsignedInteger('wrong_answers');
            $table->unsignedInteger('unanswered_answers');
            $table->decimal('score_percentage', 5, 2);
            $table->enum('grade_letter', ['A', 'B', 'C', 'D', 'E']);
            $table->string('grade_label', 100);
            $table->enum('result_status', ['submitted', 'auto_submitted']);
            $table->timestamp('calculated_at');
            $table->timestamps();

            $table->index('quiz_id');
            $table->index('grade_letter');
            $table->index('result_status');
            $table->index('score_percentage');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_results');
    }
};

