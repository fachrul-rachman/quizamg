<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attempt_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_attempt_id')->constrained('quiz_attempts');
            $table->foreignId('question_id')->constrained('questions');
            $table->foreignId('selected_option_id')->nullable()->constrained('question_options');
            $table->longText('answer_text')->nullable();
            $table->boolean('is_correct')->default(false);
            $table->timestamp('answered_at')->nullable();
            $table->timestamps();

            $table->unique(['quiz_attempt_id', 'question_id']);
            $table->index('selected_option_id');
            $table->index('is_correct');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attempt_answers');
    }
};

