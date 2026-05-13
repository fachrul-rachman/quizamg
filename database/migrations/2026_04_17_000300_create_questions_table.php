<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained('quizzes');
            $table->enum('question_type', ['multiple_choice', 'short_answer']);
            $table->longText('question_text');
            $table->string('question_image_path', 500)->nullable();
            $table->unsignedInteger('order_number');
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['quiz_id', 'order_number']);
            $table->index('question_type');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};

