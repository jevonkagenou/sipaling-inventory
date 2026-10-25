<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class UserControllerImportTest extends TestCase
{
    use RefreshDatabase;

    protected User $commissioner;

    protected User $manager;

    protected User $staff;

    protected User $auditor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->commissioner = User::where('email', 'komisaris@sipaling.com')->first();
        $this->manager = User::where('email', 'manajer@sipaling.com')->first();
        $this->staff = User::where('email', 'staf@sipaling.com')->first();
        $this->auditor = User::where('email', 'sultansyarif630@gmail.com')->first();
    }

    /**
     * Skenario 1: Guest (Tamu) dilarang mengakses template dan endpoint impor pengguna.
     */
    public function test_guest_cannot_download_user_import_template_or_import_csv(): void
    {
        $this->get('/users/import-template')
            ->assertRedirect('/login');

        $dummyFile = UploadedFile::fake()->create('users.csv', 10);
        $this->post('/users/import', ['file' => $dummyFile])
            ->assertRedirect('/login');
    }

    /**
     * Skenario 2: Peran yang tidak berwenang (Staf Gudang & Auditor Internal) dilarang mengakses fitur impor pengguna.
     */
    public function test_unauthorized_roles_cannot_access_user_import(): void
    {
        $dummyFile = UploadedFile::fake()->create('users.csv', 10);

        // Staf Gudang dilarang (403 Forbidden)
        $this->actingAs($this->staff)->get('/users/import-template')->assertForbidden();
        $this->actingAs($this->staff)->post('/users/import', ['file' => $dummyFile])->assertForbidden();

        // Auditor Internal dilarang (403 Forbidden)
        $this->actingAs($this->auditor)->get('/users/import-template')->assertForbidden();
        $this->actingAs($this->auditor)->post('/users/import', ['file' => $dummyFile])->assertForbidden();
    }

    /**
     * Skenario 3: Komisaris dan Manajer Operasional dapat mengunduh berkas template CSV dan Excel pengguna.
     */
    public function test_commissioner_and_manager_can_download_user_import_template(): void
    {
        // Komisaris (Default CSV)
        $resCommissioner = $this->actingAs($this->commissioner)->get('/users/import-template');
        $resCommissioner->assertOk();
        $resCommissioner->assertHeader('Content-Disposition', 'attachment; filename="template_import_pengguna_sipaling.csv"');

        // Manajer Operasional (CSV)
        $resManager = $this->actingAs($this->manager)->get('/users/import-template');
        $resManager->assertOk();
        $resManager->assertHeader('Content-Disposition', 'attachment; filename="template_import_pengguna_sipaling.csv"');

        // Manajer Operasional (Excel .xlsx)
        $resManagerXlsx = $this->actingAs($this->manager)->get('/users/import-template?format=xlsx');
        $resManagerXlsx->assertOk();
        $resManagerXlsx->assertHeader('Content-Disposition', 'attachment; filename="template_import_pengguna_sipaling.xlsx"');
    }

    /**
     * Skenario 4: Manajer Operasional berhasil mengimpor data pengguna baru secara massal dari Excel (.xlsx).
     */
    public function test_manager_can_bulk_import_users_from_xlsx_file(): void
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            ['name', 'email', 'phone', 'role', 'password'],
            ['Excel User One', 'excel.user1@sipaling.com', '081211112222', 'staf-gudang', 'ExcelPass123!'],
            ['Excel User Two', 'excel.user2@sipaling.com', '081233334444', 'manajer-operasional', 'ExcelPass456!'],
        ]);

        $tempPath = tempnam(sys_get_temp_dir(), 'test_users_').'.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        $file = new UploadedFile(
            $tempPath,
            'users_test.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $response = $this->actingAs($this->manager)->post('/users/import', [
            'file' => $file,
        ]);

        if (file_exists($tempPath)) {
            @unlink($tempPath);
        }

        $response->assertRedirect('/users');
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', ['email' => 'excel.user1@sipaling.com', 'name' => 'Excel User One']);
        $this->assertDatabaseHas('users', ['email' => 'excel.user2@sipaling.com', 'name' => 'Excel User Two']);

        $u1 = User::where('email', 'excel.user1@sipaling.com')->first();
        $this->assertNotNull($u1);
        $this->assertTrue($u1->hasRole('staf-gudang'));
        $this->assertTrue(Hash::check('ExcelPass123!', $u1->password));
    }

    /**
     * Skenario 5: Manajer Operasional berhasil mengimpor data pengguna baru secara massal dari CSV.
     */
    public function test_manager_can_bulk_import_users_from_csv(): void
    {
        $csvContent = "name,email,phone,role,password\n"
            ."Budi Santoso,budi.baru@sipaling.com,081234567890,staf-gudang,Kredensial123!\n"
            ."Siti Rahma,siti.baru@sipaling.com,081298765432,auditor-internal,\n";

        $file = UploadedFile::fake()->createWithContent('users.csv', $csvContent);

        $response = $this->actingAs($this->manager)->post('/users/import', [
            'file' => $file,
        ]);

        $response->assertRedirect('/users');
        $response->assertSessionHas('success');

        // Verifikasi pengguna baru pertama berhasil tersimpan di DB
        $user1 = User::where('email', 'budi.baru@sipaling.com')->first();
        $this->assertNotNull($user1);
        $this->assertEquals('Budi Santoso', $user1->name);
        $this->assertEquals('081234567890', $user1->phone);
        $this->assertTrue($user1->is_active);
        $this->assertNotNull($user1->email_verified_at);
        $this->assertTrue($user1->hasRole('staf-gudang'));
        $this->assertTrue(Hash::check('Kredensial123!', $user1->password));

        // Verifikasi pengguna kedua menggunakan kata sandi default Password123!
        $user2 = User::where('email', 'siti.baru@sipaling.com')->first();
        $this->assertNotNull($user2);
        $this->assertEquals('Siti Rahma', $user2->name);
        $this->assertTrue($user2->hasRole('auditor-internal'));
        $this->assertTrue(Hash::check('Password123!', $user2->password));

        // Verifikasi Jejak Audit (Activity Log) tercatat
        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'users',
            'event' => 'imported',
            'causer_id' => $this->manager->id,
        ]);
    }

    /**
     * Skenario 5: Komisaris berhasil mengimpor data pengguna baru secara massal dari CSV.
     */
    public function test_commissioner_can_bulk_import_users_from_csv(): void
    {
        $csvContent = "name,email,phone,role,password\n"
            ."Rendra Pratama,rendra@sipaling.com,081377889900,manajer-operasional,ManajerPass123!\n";

        $file = UploadedFile::fake()->createWithContent('users.csv', $csvContent);

        $response = $this->actingAs($this->commissioner)->post('/users/import', [
            'file' => $file,
        ]);

        $response->assertRedirect('/users');
        $response->assertSessionHas('success');

        $user = User::where('email', 'rendra@sipaling.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('manajer-operasional'));
        $this->assertTrue(Hash::check('ManajerPass123!', $user->password));

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'users',
            'event' => 'imported',
            'causer_id' => $this->commissioner->id,
        ]);
    }

    /**
     * Skenario 6: Impor melewati duplikasi email, format email tidak valid, dan peran tidak terdaftar.
     */
    public function test_import_skips_duplicate_emails_and_invalid_data(): void
    {
        // $this->manager sudah ada dengan email 'manajer@sipaling.com'
        $csvContent = "name,email,phone,role,password\n"
            ."Duplikat Manajer,manajer@sipaling.com,0811111111,manajer-operasional,Pass12345!\n"
            ."Format Salah,bukan-email,0822222222,staf-gudang,Pass12345!\n"
            ."Peran Gaib,gaib@sipaling.com,0833333333,superadmin-khayalan,Pass12345!\n"
            ."Pengguna Sah,sah@sipaling.com,0844444444,staf-gudang,Pass12345!\n";

        $file = UploadedFile::fake()->createWithContent('users_mixed.csv', $csvContent);

        $response = $this->actingAs($this->manager)->post('/users/import', [
            'file' => $file,
        ]);

        $response->assertRedirect('/users');

        // Pengguna sah harus berhasil terdaftar
        $this->assertDatabaseHas('users', ['email' => 'sah@sipaling.com', 'name' => 'Pengguna Sah']);

        // Data tidak valid tidak boleh terdaftar
        $this->assertDatabaseMissing('users', ['email' => 'bukan-email']);
        $this->assertDatabaseMissing('users', ['email' => 'gaib@sipaling.com']);

        // Verifikasi activity_log properti imported_count = 1, skipped_count = 3
        $log = ActivityLog::where('event', 'imported')->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertEquals(1, $log->properties['imported_count']);
        $this->assertEquals(3, $log->properties['skipped_count']);
    }

    /**
     * Skenario 7: Validasi menolak berkas CSV jika kolom wajib (name, email, role) tidak ada pada baris judul.
     */
    public function test_import_fails_when_required_columns_are_missing(): void
    {
        // Kehilangan kolom 'role'
        $csvContent = "name,email,phone,password\n"
            ."Tanpa Role,tanpa.role@sipaling.com,0812345678,Password123!\n";

        $file = UploadedFile::fake()->createWithContent('invalid_headers.csv', $csvContent);

        $response = $this->actingAs($this->manager)->post('/users/import', [
            'file' => $file,
        ]);

        $response->assertSessionHasErrors(['file']);
        $this->assertDatabaseMissing('users', ['email' => 'tanpa.role@sipaling.com']);
    }

    /**
     * Skenario 8: Validasi menolak jika berkas kosong.
     */
    public function test_import_fails_when_csv_file_is_empty(): void
    {
        $file = UploadedFile::fake()->createWithContent('empty.csv', '');

        $response = $this->actingAs($this->manager)->post('/users/import', [
            'file' => $file,
        ]);

        $response->assertSessionHasErrors(['file']);
    }

    /**
     * Skenario 9: Pembaruan data pengguna (Edit User) dengan domain internal sipaling.com berhasil tanpa hambatan DNS.
     */
    public function test_manager_can_update_user_with_internal_domain_email(): void
    {
        $response = $this->actingAs($this->manager)->put("/users/{$this->staff->id}", [
            'name' => 'Staf Gudang Updated',
            'email' => 'staf.updated@sipaling.com',
            'phone' => '081234567899',
            'role' => 'staf-gudang',
            'is_active' => true,
        ]);

        $response->assertRedirect('/users');
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', [
            'id' => $this->staff->id,
            'name' => 'Staf Gudang Updated',
            'email' => 'staf.updated@sipaling.com',
            'phone' => '081234567899',
        ]);
    }
}
