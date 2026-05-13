<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('short_answer_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained('questions');
            $table->string('answer_text', 255);
            $table->string('normalized_answer_text', 255);
            $table->unsignedInteger('sort_order');
            $table->timestamps();

            $table->unique(['question_id', 'normalized_answer_text']);
            $table->unique(['question_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('short_answer_keys');
    }
};

