<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_links', function (Blueprint $table) {
            $table->enum('usage_type', ['single', 'multi'])->default('single')->after('token');
            $table->timestamp('expires_at')->nullable()->after('expired_at');

            $table->index('usage_type');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('quiz_links', function (Blueprint $table) {
            $table->dropIndex(['usage_type']);
            $table->dropIndex(['expires_at']);
            $table->dropColumn(['usage_type', 'expires_at']);
        });
    }
};

