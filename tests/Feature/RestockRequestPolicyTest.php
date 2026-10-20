<?php

namespace Tests\Feature;

use App\Models\RestockRequest;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class RestockRequestPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected User $manager;

    protected User $commissioner;

    protected User $warehouseStaff;

    protected User $internalAuditor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->manager = User::where('email', 'manajer@sipaling.com')->first();
        $this->commissioner = User::where('email', 'komisaris@sipaling.com')->first();
        $this->warehouseStaff = User::where('email', 'staf@sipaling.com')->first();
        $this->internalAuditor = User::where('email', 'sultansyarif630@gmail.com')->first();
    }

    public function test_manager_can_create_restock_request_but_cannot_approve(): void
    {
        $pendingRequest = RestockRequest::create([
            'request_no' => 'REQ-POL-0001',
            'requested_by' => $this->manager->id,
            'status' => 'pending',
            'total_estimated_cost' => 5000000,
        ]);

        // Manajer boleh mengajukan (create)
        $this->assertTrue(Gate::forUser($this->manager)->allows('create', RestockRequest::class));

        // Manajer DILARANG menyetujui (approve) atau menolak (reject)
        $this->assertFalse(Gate::forUser($this->manager)->allows('approve', $pendingRequest));
        $this->assertFalse(Gate::forUser($this->manager)->allows('reject', $pendingRequest));
    }

    public function test_commissioner_can_approve_and_reject_pending_request_but_cannot_create(): void
    {
        $pendingRequest = RestockRequest::create([
            'request_no' => 'REQ-POL-0002',
            'requested_by' => $this->manager->id,
            'status' => 'pending',
            'total_estimated_cost' => 12000000,
        ]);

        // Komisaris DILARANG membuat/mengajukan restock (create)
        $this->assertFalse(Gate::forUser($this->commissioner)->allows('create', RestockRequest::class));

        // Komisaris BERHAK menyetujui (approve) dan menolak (reject) berkas yang berstatus pending
        $this->assertTrue(Gate::forUser($this->commissioner)->allows('approve', $pendingRequest));
        $this->assertTrue(Gate::forUser($this->commissioner)->allows('reject', $pendingRequest));
    }

    public function test_commissioner_cannot_approve_or_reject_non_pending_request(): void
    {
        $approvedRequest = RestockRequest::create([
            'request_no' => 'REQ-POL-0003',
            'requested_by' => $this->manager->id,
            'status' => 'approved',
            'reviewed_by' => $this->commissioner->id,
            'reviewed_at' => now(),
        ]);

        $rejectedRequest = RestockRequest::create([
            'request_no' => 'REQ-POL-0004',
            'requested_by' => $this->manager->id,
            'status' => 'rejected',
            'reviewed_by' => $this->commissioner->id,
            'reviewed_at' => now(),
            'review_notes' => 'Anggaran melebihi kuota',
        ]);

        // Berkas yang sudah disetujui / ditolak TIDAK BOLEH diproses ulang
        $this->assertFalse(Gate::forUser($this->commissioner)->allows('approve', $approvedRequest));
        $this->assertFalse(Gate::forUser($this->commissioner)->allows('reject', $approvedRequest));
        $this->assertFalse(Gate::forUser($this->commissioner)->allows('approve', $rejectedRequest));
        $this->assertFalse(Gate::forUser($this->commissioner)->allows('reject', $rejectedRequest));
    }

    public function test_warehouse_staff_and_auditor_cannot_create_nor_approve(): void
    {
        $pendingRequest = RestockRequest::create([
            'request_no' => 'REQ-POL-0005',
            'requested_by' => $this->manager->id,
            'status' => 'pending',
        ]);

        // Staf Gudang tidak punya hak create maupun approve
        $this->assertFalse(Gate::forUser($this->warehouseStaff)->allows('create', RestockRequest::class));
        $this->assertFalse(Gate::forUser($this->warehouseStaff)->allows('approve', $pendingRequest));
        $this->assertFalse(Gate::forUser($this->warehouseStaff)->allows('reject', $pendingRequest));

        // Auditor Internal tidak punya hak create maupun approve
        $this->assertFalse(Gate::forUser($this->internalAuditor)->allows('create', RestockRequest::class));
        $this->assertFalse(Gate::forUser($this->internalAuditor)->allows('approve', $pendingRequest));
        $this->assertFalse(Gate::forUser($this->internalAuditor)->allows('reject', $pendingRequest));
    }

    public function test_manager_and_commissioner_can_view_restock_requests(): void
    {
        $request = RestockRequest::create([
            'request_no' => 'REQ-POL-0006',
            'requested_by' => $this->manager->id,
            'status' => 'pending',
        ]);

        $this->assertTrue(Gate::forUser($this->manager)->allows('viewAny', RestockRequest::class));
        $this->assertTrue(Gate::forUser($this->manager)->allows('view', $request));

        $this->assertTrue(Gate::forUser($this->commissioner)->allows('viewAny', RestockRequest::class));
        $this->assertTrue(Gate::forUser($this->commissioner)->allows('view', $request));
    }
}
