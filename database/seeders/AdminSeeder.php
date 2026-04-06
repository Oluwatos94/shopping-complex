<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use ModulesShoppingComplex\Models\User;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = (string) env('ADMIN_EMAIL', 'admin@shoppingcomplex.com');
        $name  = (string) env('ADMIN_NAME', 'Super Admin');
        $password = (string) env('ADMIN_PASSWORD');

        if ($password === '') {
            $this->command->error('ADMIN_PASSWORD is not set in .env. Aborting.');

            return;
        }

        $admin = User::updateOrCreate(
            ['email' => $email],
            [
                'name'              => $name,
                'password'          => Hash::make($password),
                'role'              => 'admin',
                'email_verified_at' => now(),
            ]
        );

        $this->command->info("Admin account ready: {$admin->email}");
    }
}
