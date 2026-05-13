<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_links', function (Blueprint $table) {
            $table->string('google_drive_folder_id', 200)->nullable()->after('expires_at');
            $table->string('google_drive_folder_url', 500)->nullable()->after('google_drive_folder_id');

            $table->index('google_drive_folder_id');
        });
    }

    public function down(): void
    {
        Schema::table('quiz_links', function (Blueprint $table) {
            $table->dropIndex(['google_drive_folder_id']);
            $table->dropColumn(['google_drive_folder_id', 'google_drive_folder_url']);
        });
    }
};

