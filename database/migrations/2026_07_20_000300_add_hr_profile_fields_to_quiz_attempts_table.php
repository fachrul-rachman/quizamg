<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table): void {
            $table->unsignedSmallInteger('participant_age')->nullable()->after('participant_applied_for');
            $table->decimal('participant_height_cm', 5, 2)->nullable()->after('participant_age');
            $table->decimal('participant_weight_kg', 5, 2)->nullable()->after('participant_height_cm');
            $table->string('participant_last_job')->nullable()->after('participant_weight_kg');
            $table->string('participant_last_company')->nullable()->after('participant_last_job');
            $table->string('participant_current_domicile')->nullable()->after('participant_last_company');
        });
    }

    public function down(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table): void {
            $table->dropColumn([
                'participant_age',
                'participant_height_cm',
                'participant_weight_kg',
                'participant_last_job',
                'participant_last_company',
                'participant_current_domicile',
            ]);
        });
    }
};
