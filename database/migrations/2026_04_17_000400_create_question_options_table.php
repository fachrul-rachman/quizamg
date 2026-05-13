<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained('questions');
            $table->enum('option_key', ['A', 'B', 'C', 'D', 'E']);
            $table->longText('option_text')->nullable();
            $table->string('option_image_path', 500)->nullable();
            $table->boolean('is_correct')->default(false);
            $table->unsignedInteger('sort_order');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['question_id', 'option_key']);
            $table->unique(['question_id', 'sort_order']);
            $table->index('is_correct');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_options');
    }
};

