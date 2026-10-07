<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Definisi Hak Akses (Permissions)
        $permissions = [
            'inventory.view',
            'inventory.manage',
            'transactions.view',
            'transactions.create',
            'analytics.view',
            'restock.request',
            'restock.approve',
            'audit.view',
            'audit.export',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        // 2. Definisi 4 Peran Statis Spatie (Roles) & Pemetaan Hak Akses
        $rolesWithPermissions = [
            'komisaris' => [
                'inventory.view',
                'transactions.view',
                'analytics.view',
                'restock.approve',
                'audit.view',
            ],
            'manajer-operasional' => [
                'inventory.view',
                'inventory.manage',
                'transactions.view',
                'analytics.view',
                'restock.request',
            ],
            'staf-gudang' => [
                'inventory.view',
                'transactions.view',
                'transactions.create',
            ],
            'auditor-internal' => [
                'inventory.view',
                'transactions.view',
                'audit.view',
                'audit.export',
            ],
        ];

        foreach ($rolesWithPermissions as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);
            $role->syncPermissions($rolePermissions);
        }

        // 3. Pembuatan Akun Default untuk Setiap Peran (Primary Key UUID v4 otomatis)
        $defaultUsers = [
            [
                'name' => 'Komisaris SIPALING',
                'email' => 'komisaris@sipaling.com',
                'role' => 'komisaris',
                'phone' => '081200000001',
            ],
            [
                'name' => 'Manajer Operasional',
                'email' => 'manajer@sipaling.com',
                'role' => 'manajer-operasional',
                'phone' => '081200000002',
            ],
            [
                'name' => 'Staf Gudang',
                'email' => 'staf@sipaling.com',
                'role' => 'staf-gudang',
                'phone' => '081200000003',
            ],
            [
                'name' => 'Auditor Internal',
                'email' => 'sultansyarif630@gmail.com',
                'role' => 'auditor-internal',
                'phone' => '081200000004',
            ],
        ];

        foreach ($defaultUsers as $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => Hash::make('password'),
                    'phone' => $userData['phone'],
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );

            // Menghubungkan pengguna dengan perannya
            $user->syncRoles([$userData['role']]);
        }
    }
}
