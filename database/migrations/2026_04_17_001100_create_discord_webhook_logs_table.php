<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discord_webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_result_id')->constrained('quiz_results');
            $table->string('webhook_url', 1000);
            $table->longText('payload_json');
            $table->integer('response_status_code')->nullable();
            $table->longText('response_body')->nullable();
            $table->boolean('is_success')->default(false);
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index('quiz_result_id');
            $table->index('is_success');
            $table->index('sent_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discord_webhook_logs');
    }
};

