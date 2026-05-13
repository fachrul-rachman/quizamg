<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $users = [
            [
                'email' => 'superadmin@amg.com',
                'name' => 'Super Admin',
                'role' => 'super_admin',
            ]
        ];

        foreach ($users as $u) {
            $user = User::withTrashed()->updateOrCreate(
                ['email' => $u['email']],
                [
                    'name' => $u['name'],
                    'password' => Hash::make('password'),
                    'role' => $u['role'],
                    'is_active' => true,
                ]
            );

            if ($user->trashed()) {
                $user->restore();
            }
        }
    }
}
