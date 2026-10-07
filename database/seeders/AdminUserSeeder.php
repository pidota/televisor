<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::query()->where('name', 'admin')->first();

        if ($adminRole === null) {
            return;
        }

        $user = User::query()->firstOrCreate(
            ['email' => 'admin@televisor.local'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('Televisor2026!'),
            ]
        );

        $user->roles()->syncWithoutDetaching([$adminRole->id]);
    }
}
