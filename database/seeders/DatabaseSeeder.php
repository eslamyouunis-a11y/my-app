<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Roles
        |--------------------------------------------------------------------------
        */
        $roles = [
            'admin',
            'branch',
            'merchant',
            'courier',
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role]);
        }

        /*
        |--------------------------------------------------------------------------
        | Users (One per dashboard)
        |--------------------------------------------------------------------------
        */

        // Admin
        $admin = User::firstOrCreate(
            ['email' => 'admin@app.com'],
            [
                'name' => 'System Admin',
                'password' => Hash::make('password'),
            ]
        );
        $admin->syncRoles(['admin']);

        // Branch
        $branch = User::firstOrCreate(
            ['email' => 'branch@app.com'],
            [
                'name' => 'Branch User',
                'password' => Hash::make('password'),
            ]
        );
        $branch->syncRoles(['branch']);

        // Merchant
        $merchant = User::firstOrCreate(
            ['email' => 'merchant@app.com'],
            [
                'name' => 'Merchant User',
                'password' => Hash::make('password'),
            ]
        );
        $merchant->syncRoles(['merchant']);

        // Courier
        $courier = User::firstOrCreate(
            ['email' => 'courier@app.com'],
            [
                'name' => 'Courier User',
                'password' => Hash::make('password'),
            ]
        );
        $courier->syncRoles(['courier']);

        /*
        |--------------------------------------------------------------------------
        | Geography Seeders
        |--------------------------------------------------------------------------
        */
        $this->call([
            GovernorateSeeder::class,
            AreaSeeder::class,
        ]);
    }
}
