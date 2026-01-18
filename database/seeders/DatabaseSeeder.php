<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call(RoleSeeder::class);

        $superadminRole = Role::where('name', 'superadmin')->first();

        User::firstOrCreate(
            ['email' => 'superadmin@polytechnic.edu.mv'],
            [
                'name' => 'System Superadmin',
                'password' => Hash::make('ChangeMe123!'),
                'role_id' => $superadminRole->id,
                'is_active' => true,
            ]
        );
    }
}
