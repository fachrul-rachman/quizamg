<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discord_link_summary_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_link_id')->constrained('quiz_links');
            $table->string('webhook_url', 500);
            $table->longText('payload_json');
            $table->unsignedSmallInteger('response_status_code')->nullable();
            $table->longText('response_body')->nullable();
            $table->boolean('is_success')->default(false);
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->unique('quiz_link_id');
            $table->index('is_success');
            $table->index('sent_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discord_link_summary_logs');
    }
};

