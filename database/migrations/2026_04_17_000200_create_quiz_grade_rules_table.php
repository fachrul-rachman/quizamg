<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_grade_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained('quizzes');
            $table->enum('grade_letter', ['A', 'B', 'C', 'D', 'E']);
            $table->string('label', 100);
            $table->decimal('min_score', 5, 2);
            $table->decimal('max_score', 5, 2);
            $table->unsignedInteger('sort_order');
            $table->timestamps();

            $table->unique(['quiz_id', 'grade_letter']);
            $table->unique(['quiz_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_grade_rules');
    }
};

