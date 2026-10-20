<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    protected User $auditor;

    protected User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->auditor = User::where('email', 'sultansyarif630@gmail.com')->first();
        $this->staff = User::where('email', 'staf@sipaling.com')->first();
    }

    public function test_unauthenticated_user_cannot_access_audit_page(): void
    {
        $response = $this->get('/audit');
        $response->assertRedirect('/login');
    }

    public function test_unauthorized_role_cannot_access_audit_page(): void
    {
        $response = $this->actingAs($this->staff)->get('/audit');
        $response->assertStatus(403);
    }

    public function test_auditor_can_access_audit_page(): void
    {
        $response = $this->actingAs($this->auditor)->get('/audit');
        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Audit/Index')
            ->has('logs.data')
            ->has('stats')
            ->has('filters')
        );
    }

    public function test_auditor_can_search_audit_logs_by_description(): void
    {
        ActivityLog::create([
            'log_name' => 'inventory',
            'description' => 'Menambahkan produk Beras Ramos 5kg',
            'event' => 'created',
            'created_at' => now(),
        ]);

        ActivityLog::create([
            'log_name' => 'inventory',
            'description' => 'Memperbarui stok Minyak Sawit',
            'event' => 'updated',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($this->auditor)->get('/audit?search=Beras');
        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Audit/Index')
            ->where('filters.search', 'Beras')
            ->has('logs.data', 1)
            ->where('logs.data.0.description', 'Menambahkan produk Beras Ramos 5kg')
        );
    }

    public function test_auditor_can_filter_audit_logs_by_event(): void
    {
        ActivityLog::create([
            'log_name' => 'inventory',
            'description' => 'Membuat produk baru',
            'event' => 'created',
            'created_at' => now(),
        ]);

        ActivityLog::create([
            'log_name' => 'inventory',
            'description' => 'Menghapus kategori lama',
            'event' => 'deleted',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($this->auditor)->get('/audit?event=deleted');
        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Audit/Index')
            ->where('filters.event', 'deleted')
            ->has('logs.data', 1)
            ->where('logs.data.0.event', 'deleted')
        );
    }

    public function test_auditor_can_filter_audit_logs_by_date_range(): void
    {
        ActivityLog::create([
            'log_name' => 'inventory',
            'description' => 'Log lama 5 hari lalu',
            'event' => 'created',
            'created_at' => now()->subDays(5),
        ]);

        ActivityLog::create([
            'log_name' => 'inventory',
            'description' => 'Log hari ini khusus rentang',
            'event' => 'updated',
            'created_at' => now(),
        ]);

        $startDate = now()->subDays(1)->format('Y-m-d');
        $endDate = now()->addDays(1)->format('Y-m-d');

        $response = $this->actingAs($this->auditor)->get("/audit?start_date={$startDate}&end_date={$endDate}&search=khusus+rentang");
        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Audit/Index')
            ->has('logs.data', 1)
            ->where('logs.data.0.description', 'Log hari ini khusus rentang')
        );
    }
}
