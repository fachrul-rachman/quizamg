<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('division', 20)->default('business')->after('role')->index();
        });

        Schema::table('quizzes', function (Blueprint $table): void {
            $table->string('division', 20)->default('business')->after('description')->index();
        });
    }

    public function down(): void
    {
        Schema::table('quizzes', function (Blueprint $table): void {
            $table->dropIndex(['division']);
            $table->dropColumn('division');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['division']);
            $table->dropColumn('division');
        });
    }
};
