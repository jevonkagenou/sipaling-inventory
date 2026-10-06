<?php

namespace Database\Seeders;

class DatabaseSeeder extends Seeder
{

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleAndPermissionSeeder::class,
            InventoryCsvSeeder::class,
        ]);
    }
}
