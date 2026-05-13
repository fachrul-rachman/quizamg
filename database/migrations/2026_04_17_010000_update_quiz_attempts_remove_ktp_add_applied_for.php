<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->string('participant_applied_for', 255)->after('participant_name');
            $table->index('participant_applied_for');
        });

        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->dropIndex(['participant_ktp_number']);
            $table->dropColumn('participant_ktp_number');
        });
    }

    public function down(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->string('participant_ktp_number', 50)->after('participant_name');
            $table->index('participant_ktp_number');
        });

        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->dropIndex(['participant_applied_for']);
            $table->dropColumn('participant_applied_for');
        });
    }
};

