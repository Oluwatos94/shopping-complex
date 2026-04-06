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
        $email = (string) config('services.admin.email', 'admin@shoppingcomplex.com');
        $name = (string) config('services.admin.name', 'Super Admin');
        $password = (string) config('services.admin.password', '');

        if ($password === '') {
            $this->command->error('ADMIN_PASSWORD is not set in .env. Aborting.');

            return;
        }

        $admin = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );

        $this->command->info("Admin account ready: {$admin->email}");
    }
}
