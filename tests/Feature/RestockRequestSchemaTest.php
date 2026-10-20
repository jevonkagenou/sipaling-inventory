<?php

namespace Tests\Feature;

use App\Models\RestockRequest;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class RestockRequestSchemaTest extends TestCase
{
    use RefreshDatabase;

    protected User $manager;

    protected User $commissioner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->manager = User::where('email', 'manajer@sipaling.com')->first();
        $this->commissioner = User::where('email', 'komisaris@sipaling.com')->first();
    }

    public function test_can_create_restock_request_with_uuid_v4(): void
    {
        $request = RestockRequest::create([
            'request_no' => 'REQ-202610-0001',
            'requested_by' => $this->manager->id,
            'status' => 'pending',
            'total_estimated_cost' => 15000000.00,
            'general_notes' => 'Pengadaan stok kritis monitor & kabel LAN',
        ]);

        $this->assertNotEmpty($request->id);
        $this->assertTrue(Str::isUuid($request->id), 'ID restock_requests harus bertipe UUID v4 36 karakter');
        $this->assertEquals('pending', $request->status);
        $this->assertEquals('REQ-202610-0001', $request->request_no);
        $this->assertEquals($this->manager->id, $request->requester->id);
    }

    public function test_restock_request_status_enum_values(): void
    {
        $statuses = ['pending', 'approved', 'rejected'];

        foreach ($statuses as $index => $status) {
            $req = RestockRequest::create([
                'request_no' => "REQ-STATUS-000{$index}",
                'requested_by' => $this->manager->id,
                'status' => $status,
                'reviewed_by' => $status !== 'pending' ? $this->commissioner->id : null,
                'reviewed_at' => $status !== 'pending' ? now() : null,
                'review_notes' => $status === 'rejected' ? 'Anggaran belum disetujui dewan' : null,
            ]);

            $this->assertEquals($status, $req->status);
        }
    }

    public function test_restock_request_requester_and_reviewer_relationships(): void
    {
        $request = RestockRequest::create([
            'request_no' => 'REQ-REL-0001',
            'requested_by' => $this->manager->id,
            'status' => 'approved',
            'reviewed_by' => $this->commissioner->id,
            'reviewed_at' => now(),
            'review_notes' => 'Disetujui untuk PO batch 1',
            'total_estimated_cost' => 7500000,
        ]);

        // Verifikasi relasi dari RestockRequest ke User
        $this->assertInstanceOf(User::class, $request->requester);
        $this->assertEquals($this->manager->id, $request->requester->id);
        $this->assertEquals('Manajer Operasional', $request->requester->name);

        $this->assertInstanceOf(User::class, $request->reviewer);
        $this->assertEquals($this->commissioner->id, $request->reviewer->id);
        $this->assertEquals('Komisaris SIPALING', $request->reviewer->name);

        // Verifikasi relasi timbal balik (reciprocal) dari User ke RestockRequest
        $this->assertTrue($this->manager->requestedRestocks()->where('id', $request->id)->exists());
        $this->assertTrue($this->commissioner->reviewedRestocks()->where('id', $request->id)->exists());
    }

    public function test_restock_request_scopes_and_status_helpers(): void
    {
        $pendingReq = RestockRequest::create([
            'request_no' => 'REQ-SCP-001',
            'requested_by' => $this->manager->id,
            'status' => 'pending',
        ]);

        $approvedReq = RestockRequest::create([
            'request_no' => 'REQ-SCP-002',
            'requested_by' => $this->manager->id,
            'status' => 'approved',
            'reviewed_by' => $this->commissioner->id,
            'reviewed_at' => now(),
        ]);

        $rejectedReq = RestockRequest::create([
            'request_no' => 'REQ-SCP-003',
            'requested_by' => $this->manager->id,
            'status' => 'rejected',
            'reviewed_by' => $this->commissioner->id,
            'reviewed_at' => now(),
            'review_notes' => 'Alasan penolakan anggaran',
        ]);

        // Uji query scopes
        $this->assertEquals(1, RestockRequest::pending()->where('id', $pendingReq->id)->count());
        $this->assertEquals(1, RestockRequest::approved()->where('id', $approvedReq->id)->count());
        $this->assertEquals(1, RestockRequest::rejected()->where('id', $rejectedReq->id)->count());

        // Uji helper methods
        $this->assertTrue($pendingReq->isPending());
        $this->assertFalse($pendingReq->isApproved());
        $this->assertTrue($approvedReq->isApproved());
        $this->assertFalse($approvedReq->isRejected());
        $this->assertTrue($rejectedReq->isRejected());
        $this->assertFalse($rejectedReq->isPending());
    }
}
