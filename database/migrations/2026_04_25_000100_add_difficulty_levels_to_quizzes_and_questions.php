<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->boolean('difficulty_levels_enabled')
                ->default(false)
                ->after('instant_feedback_enabled');
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->enum('difficulty_level', ['mudah', 'sedang', 'sulit', 'sangat_sulit'])
                ->default('mudah')
                ->after('question_image_path');

            $table->index('difficulty_level');
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropIndex(['difficulty_level']);
            $table->dropColumn('difficulty_level');
        });

        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropColumn('difficulty_levels_enabled');
        });
    }
};
