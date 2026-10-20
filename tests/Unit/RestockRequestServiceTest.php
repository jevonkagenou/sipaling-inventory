<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\RestockRequestService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RestockRequestServiceTest extends TestCase
{
    use RefreshDatabase;

    protected RestockRequestService $service;

    protected User $manager;

    protected User $commissioner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->service = new RestockRequestService;
        $this->manager = User::where('email', 'manajer@sipaling.com')->first();
        $this->commissioner = User::where('email', 'komisaris@sipaling.com')->first();
    }

    public function test_generate_request_number_creates_valid_format(): void
    {
        $number = $this->service->generateRequestNumber();

        $expectedPrefix = 'REQ-'.now()->format('Ym').'-';
        $this->assertStringStartsWith($expectedPrefix, $number);
        $this->assertEquals(15, strlen($number)); // REQ-YYYYMM-XXXX (4 + 6 + 1 + 4 = 15)
    }

    public function test_create_request_initializes_with_pending_status_and_audit(): void
    {
        $request = $this->service->createRequest([
            'total_estimated_cost' => 12500000,
            'general_notes' => 'Pengadaan stok kritis keyboard & mouse wireless',
        ], $this->manager);

        $this->assertEquals('pending', $request->status);
        $this->assertEquals($this->manager->id, $request->requested_by);
        $this->assertEquals(12500000, (float) $request->total_estimated_cost);

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'restock',
            'event' => 'restock_requested',
            'causer_id' => $this->manager->id,
        ]);
    }

    public function test_approve_transitions_from_pending_to_approved(): void
    {
        $request = $this->service->createRequest([
            'total_estimated_cost' => 5000000,
        ], $this->manager);

        $approved = $this->service->approve($request, $this->commissioner, 'Anggaran disetujui');

        $this->assertEquals('approved', $approved->status);
        $this->assertEquals($this->commissioner->id, $approved->reviewed_by);
        $this->assertNotNull($approved->reviewed_at);
        $this->assertEquals('Anggaran disetujui', $approved->review_notes);

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'restock',
            'event' => 'restock_approved',
            'causer_id' => $this->commissioner->id,
        ]);
    }

    public function test_anti_bypass_prevents_reapproving_already_approved_request(): void
    {
        $request = $this->service->createRequest([], $this->manager);
        $this->service->approve($request, $this->commissioner, 'Persetujuan pertama');

        $this->expectException(ValidationException::class);
        $this->service->approve($request, $this->commissioner, 'Persetujuan kedua (Bypass)');
    }

    public function test_anti_bypass_prevents_approving_rejected_request(): void
    {
        $request = $this->service->createRequest([], $this->manager);
        $this->service->reject($request, $this->commissioner, 'Alasan ditolak');

        $this->expectException(ValidationException::class);
        $this->service->approve($request, $this->commissioner, 'Mencoba approve berkas yang ditolak');
    }

    public function test_reject_transitions_from_pending_to_rejected_with_mandatory_notes(): void
    {
        $request = $this->service->createRequest([
            'total_estimated_cost' => 20000000,
        ], $this->manager);

        $rejected = $this->service->reject($request, $this->commissioner, 'Anggaran melebihi batas triwulan');

        $this->assertEquals('rejected', $rejected->status);
        $this->assertEquals($this->commissioner->id, $rejected->reviewed_by);
        $this->assertNotNull($rejected->reviewed_at);
        $this->assertEquals('Anggaran melebihi batas triwulan', $rejected->review_notes);

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'restock',
            'event' => 'restock_rejected',
            'causer_id' => $this->commissioner->id,
        ]);
    }

    public function test_reject_fails_without_mandatory_reason(): void
    {
        $request = $this->service->createRequest([], $this->manager);

        $this->expectException(ValidationException::class);
        $this->service->reject($request, $this->commissioner, '   '); // Empty spaces
    }

    public function test_anti_bypass_prevents_rejecting_already_approved_request(): void
    {
        $request = $this->service->createRequest([], $this->manager);
        $this->service->approve($request, $this->commissioner, 'Disetujui');

        $this->expectException(ValidationException::class);
        $this->service->reject($request, $this->commissioner, 'Mencoba menolak berkas yang sudah approved');
    }
}
