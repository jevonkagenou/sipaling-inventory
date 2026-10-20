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
}
