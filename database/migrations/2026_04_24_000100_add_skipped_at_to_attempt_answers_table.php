<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attempt_answers', function (Blueprint $table) {
            $table->timestamp('skipped_at')->nullable()->after('answered_at');
            $table->index('skipped_at');
        });
    }

    public function down(): void
    {
        Schema::table('attempt_answers', function (Blueprint $table) {
            $table->dropIndex(['skipped_at']);
            $table->dropColumn('skipped_at');
        });
    }
};

