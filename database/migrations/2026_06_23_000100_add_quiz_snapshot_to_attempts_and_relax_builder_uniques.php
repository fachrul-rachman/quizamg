<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->longText('quiz_snapshot')->nullable()->after('time_limit_minutes');
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->dropUnique(['quiz_id', 'order_number']);
            $table->index(['quiz_id', 'order_number']);
        });

        Schema::table('question_options', function (Blueprint $table) {
            $table->dropUnique(['question_id', 'option_key']);
            $table->dropUnique(['question_id', 'sort_order']);
            $table->index(['question_id', 'option_key']);
            $table->index(['question_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::table('question_options', function (Blueprint $table) {
            $table->dropIndex(['question_id', 'option_key']);
            $table->dropIndex(['question_id', 'sort_order']);
            $table->unique(['question_id', 'option_key']);
            $table->unique(['question_id', 'sort_order']);
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->dropIndex(['quiz_id', 'order_number']);
            $table->unique(['quiz_id', 'order_number']);
        });

        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->dropColumn('quiz_snapshot');
        });
    }
};
