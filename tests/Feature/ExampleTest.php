<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test public pages.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    /**
     * Smoke test all major authenticated pages across different roles.
     */
    public function test_all_core_pages_render_without_error(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);

        $staff = User::where('email', 'staf@sipaling.com')->first();
        $manager = User::where('email', 'manajer@sipaling.com')->first();
        $auditor = User::where('email', 'sultansyarif630@gmail.com')->first();
        $komisaris = User::where('email', 'komisaris@sipaling.com')->first();

        // Staf Gudang: operasional gudang & input mutasi
        foreach (['/dashboard', '/inventory', '/transactions', '/transactions/inbound', '/transactions/outbound', '/analytics', '/profile'] as $route) {
            $this->actingAs($staff)->get($route)->assertOk();
        }

        // Manajer Operasional: inventaris, mutasi, pengguna, restock
        foreach (['/dashboard', '/inventory', '/transactions', '/transactions/inbound', '/transactions/outbound', '/analytics', '/restock', '/users', '/profile'] as $route) {
            $this->actingAs($manager)->get($route)->assertOk();
        }

        // Auditor Internal: pengawasan & audit log
        foreach (['/dashboard', '/inventory', '/transactions', '/analytics', '/audit', '/profile'] as $route) {
            $this->actingAs($auditor)->get($route)->assertOk();
        }

        // Komisaris: persetujuan restock, audit log, analitik & supervisi
        foreach (['/dashboard', '/inventory', '/transactions', '/analytics', '/restock', '/restock/approval', '/audit', '/users', '/profile'] as $route) {
            $this->actingAs($komisaris)->get($route)->assertOk();
        }
    }
}
