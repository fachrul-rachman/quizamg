<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('discord_link_summary_logs', function (Blueprint $table) {
            $table->dropUnique('discord_link_summary_logs_quiz_link_id_unique');
            $table->unique(['quiz_link_id', 'webhook_url']);
        });
    }

    public function down(): void
    {
        Schema::table('discord_link_summary_logs', function (Blueprint $table) {
            $table->dropUnique('discord_link_summary_logs_quiz_link_id_webhook_url_unique');
            $table->unique('quiz_link_id');
        });
    }
};

