<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('quizzes', 'category_id')) {
            DB::table('quizzes')->update(['category_id' => null]);

            try {
                Schema::table('quizzes', function (Blueprint $table) {
                    $table->dropIndex('quizzes_category_id_index');
                });
            } catch (\Throwable) {
                // ignore
            }

            Schema::table('quizzes', function (Blueprint $table) {
                $table->dropConstrainedForeignId('category_id');
            });
        }

        Schema::dropIfExists('quiz_categories');
    }

    public function down(): void
    {
        Schema::create('quiz_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255)->unique();
            $table->timestamps();
        });

        Schema::table('quizzes', function (Blueprint $table) {
            $table->foreignId('category_id')
                ->nullable()
                ->after('description')
                ->constrained('quiz_categories')
                ->restrictOnDelete();

            $table->index('category_id');
        });
    }
};
