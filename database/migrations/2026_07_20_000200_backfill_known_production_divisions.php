<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public $withinTransaction = true;

    public function up(): void
    {
        DB::table('users')
            ->whereIn('email', [
                'superadmin@amg.com',
                'adminbd@amg.com',
            ])
            ->update(['division' => 'business']);

        DB::table('users')
            ->where('email', 'adminhrd@amg.com')
            ->update(['division' => 'hr']);

        $hrAdminId = DB::table('users')
            ->where('email', 'adminhrd@amg.com')
            ->value('id');

        if ($hrAdminId !== null) {
            DB::table('quizzes')
                ->where('title', 'WPT')
                ->where('created_by', $hrAdminId)
                ->update(['division' => 'hr']);
        }
    }

    public function down(): void
    {
        // Division assignments represent production data and are not reversed.
    }
};
