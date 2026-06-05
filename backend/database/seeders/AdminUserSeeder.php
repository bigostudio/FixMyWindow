<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\Enums\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@fixmywindow.in'],
            [
                'name'      => 'Super Admin',
                'phone'     => '9000000000',
                'password'  => Hash::make('Admin@1234'),
                'role'      => Role::SuperAdmin,
                'is_active' => true,
            ]
        );
    }
}
