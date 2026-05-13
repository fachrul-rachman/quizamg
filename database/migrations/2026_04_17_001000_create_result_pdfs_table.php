<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('result_pdfs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_result_id')->unique()->constrained('quiz_results');
            $table->string('file_name', 255);
            $table->string('local_path', 500)->nullable();
            $table->string('google_drive_file_id', 255)->nullable();
            $table->string('google_drive_url', 1000)->nullable();
            $table->timestamp('generated_at');
            $table->timestamp('uploaded_at')->nullable();
            $table->timestamps();

            $table->index('google_drive_file_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('result_pdfs');
    }
};

