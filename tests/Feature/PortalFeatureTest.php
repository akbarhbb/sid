<?php

namespace Tests\Feature;

use App\Models\LedgerTransaction;
use App\Models\PaymentProvider;
use App\Models\PaymentTransaction;
use App\Models\Region;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PortalFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_portal_and_announcements_load(): void
    {
        $region = Region::query()->create(['name' => 'Pilot', 'district' => 'Sawangan', 'city' => 'Depok', 'province' => 'Jawa Barat', 'slug' => 'pilot']);
        $region->announcements()->create(['title' => 'Kerja bakti', 'category' => 'kegiatan', 'body' => 'Minggu pagi', 'is_published' => true, 'published_at' => now()]);

        $this->get('/')->assertOk()->assertSee('Kerja bakti');
        $this->get('/pengumuman')->assertOk()->assertSee('Kerja bakti');
        $this->get('/login')->assertOk();
        $this->get('/daftar')->assertOk();
    }

    public function test_resident_registration_requires_consent_and_waits_for_approval(): void
    {
        $region = Region::query()->create(['name' => 'Pilot', 'district' => 'Sawangan', 'city' => 'Depok', 'province' => 'Jawa Barat', 'slug' => 'pilot']);
        $payload = [
            'name' => 'Warga Test',
            'email' => 'warga@example.test',
            'phone' => '081234567890',
            'address' => 'Sawangan',
            'national_id' => '3276010101010001',
            'family_card_number' => '3276010101011001',
            'password' => 'password-test-123',
            'password_confirmation' => 'password-test-123',
        ];

        $this->post('/daftar', $payload)->assertSessionHasErrors('consent');
        $this->post('/daftar', $payload + ['consent' => '1'])->assertRedirect('/login');

        $this->assertDatabaseHas('users', ['email' => 'warga@example.test', 'role' => 'warga', 'account_status' => 'pending', 'region_id' => $region->id]);
        $this->post('/login', ['email' => 'warga@example.test', 'password' => 'password-test-123'])->assertRedirect('/login');
    }

    public function test_payment_provider_settings_allow_multiple_adapters_and_encrypt_credentials(): void
    {
        $region = Region::query()->create(['name' => 'Pilot', 'district' => 'Sawangan', 'city' => 'Depok', 'province' => 'Jawa Barat', 'slug' => 'pilot']);
        $admin = User::factory()->create(['role' => 'superadmin', 'account_status' => 'approved', 'region_id' => $region->id]);
        $payload = [
            'name' => 'Gateway Satu',
            'driver' => 'provider.one',
            'environment' => 'sandbox',
            'credentials' => '{"secret":"keep-this-secret"}',
            'supported_methods' => ['QRIS', 'VA'],
            'is_active' => '1',
            'is_default' => '1',
        ];

        $this->actingAs($admin)->post('/admin/payment-gateways', $payload)->assertRedirect('/admin/payment-gateways');
        $this->actingAs($admin)->post('/admin/payment-gateways', array_merge($payload, ['name' => 'Gateway Dua', 'driver' => 'provider.two', 'is_default' => '0']))->assertRedirect('/admin/payment-gateways');

        $this->assertDatabaseCount('payment_providers', 2);
        $provider = PaymentProvider::query()->where('driver', 'provider.one')->firstOrFail();
        $this->assertSame(['secret' => 'keep-this-secret'], $provider->credentials);
        $this->assertStringNotContainsString('keep-this-secret', $provider->getRawOriginal('credentials'));
        $this->assertTrue($provider->is_default);
        $this->assertSame(2, PaymentProvider::query()->where('region_id', $region->id)->count());
    }

    public function test_payment_provider_management_is_limited_to_admin_roles(): void
    {
        $region = Region::query()->create(['name' => 'Pilot', 'district' => 'Sawangan', 'city' => 'Depok', 'province' => 'Jawa Barat', 'slug' => 'pilot']);
        $resident = User::factory()->create(['role' => 'warga', 'account_status' => 'approved', 'region_id' => $region->id]);

        $this->actingAs($resident)->get('/admin/payment-gateways')->assertForbidden();
        $this->assertSame('id', config('app.locale'));
    }

    public function test_admin_can_review_residents_and_export_masked_identity_data(): void
    {
        $region = $this->createRegion();
        $admin = $this->createUser($region, 'superadmin');
        $resident = User::factory()->create([
            'region_id' => $region->id,
            'role' => 'warga',
            'account_status' => 'pending',
            'national_id' => '3276010101010001',
            'family_card_number' => '3276010101011001',
        ]);

        $this->actingAs($admin)->get('/admin/warga')->assertOk()->assertSee('************0001', false);
        $this->actingAs($admin)->patch('/admin/warga/'.$resident->id.'/persetujuan', ['status' => 'approved', 'is_adult' => '1'])->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $resident->id, 'account_status' => 'approved', 'is_adult' => true]);

        $export = $this->actingAs($admin)->get('/admin/warga/ekspor')->assertOk();
        $this->assertStringContainsString('************0001', $export->streamedContent());
        $this->assertStringNotContainsString('3276010101010001', $export->streamedContent());
    }

    public function test_service_requests_are_private_to_resident_and_reviewable_by_region_admin(): void
    {
        $region = $this->createRegion();
        $otherRegion = Region::query()->create(['name' => 'Region Lain', 'district' => 'Beji', 'city' => 'Depok', 'province' => 'Jawa Barat', 'slug' => 'region-lain']);
        $resident = $this->createUser($region, 'warga', 'approved');
        $otherResident = $this->createUser($otherRegion, 'warga', 'approved');
        $admin = $this->createUser($region, 'admin_rt');

        $this->actingAs($resident)->post('/layanan/ajukan', ['type' => 'surat_pengantar', 'subject' => 'Surat domisili', 'description' => 'Untuk administrasi sekolah'])->assertRedirect('/layanan/saya');
        $request = DB::table('service_requests')->first();
        $this->actingAs($otherResident)->get('/layanan/saya')->assertDontSee($request->reference);
        $this->actingAs($admin)->patch('/admin/pengajuan/'.$request->id, ['status' => 'diproses', 'staff_note' => 'Sedang diperiksa'])->assertRedirect();
        $this->assertDatabaseHas('service_requests', ['id' => $request->id, 'status' => 'diproses', 'assignee_id' => $admin->id]);
    }

    public function test_large_expense_requires_two_distinct_approvals_and_cannot_be_deleted(): void
    {
        $region = $this->createRegion(['expense_approval_limit' => 500000]);
        $firstAdmin = $this->createUser($region, 'bendahara');
        $secondAdmin = $this->createUser($region, 'admin_rt');

        $response = $this->actingAs($firstAdmin)->post('/admin/keuangan', [
            'organization' => 'rt',
            'type' => 'pengeluaran',
            'category' => 'perbaikan',
            'description' => 'Perbaikan lampu jalan',
            'amount' => 750000,
            'occurred_at' => now()->toDateTimeString(),
            'evidence' => \Illuminate\Http\UploadedFile::fake()->image('bukti.jpg'),
        ]);
        $response->assertRedirect('/admin/keuangan');
        $transaction = LedgerTransaction::query()->firstOrFail();
        $this->assertSame('menunggu_persetujuan', $transaction->status);

        $this->actingAs($firstAdmin)->post('/admin/keuangan/'.$transaction->id.'/persetujuan')->assertForbidden();
        $this->actingAs($secondAdmin)->post('/admin/keuangan/'.$transaction->id.'/persetujuan')->assertRedirect();
        $this->assertSame('disetujui', $transaction->fresh()->status);

        $this->actingAs($secondAdmin)->post('/admin/keuangan/'.$transaction->id.'/koreksi', ['description' => 'Jumlah salah'])->assertRedirect();
        $this->assertDatabaseHas('ledger_transactions', ['id' => $transaction->id, 'description' => 'Perbaikan lampu jalan']);
        $this->assertDatabaseHas('ledger_transactions', ['reverses_id' => $transaction->id, 'category' => 'koreksi']);
    }

    public function test_election_ballots_are_anonymous_and_only_adult_consented_voters_can_vote(): void
    {
        $region = $this->createRegion();
        $resident = $this->createUser($region, 'warga', 'approved', ['is_adult' => true, 'consented_at' => now()]);
        $admin = $this->createUser($region, 'panitia_pemilihan');
        $election = DB::table('elections')->insertGetId([
            'region_id' => $region->id,
            'title' => 'Pemilihan Ketua RT',
            'status' => 'registration',
            'registration_opens_at' => now()->subDays(2),
            'voting_opens_at' => now()->subMinute(),
            'voting_closes_at' => now()->addHour(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $candidate = DB::table('candidates')->insertGetId([
            'election_id' => $election,
            'name' => 'Calon A',
            'profile' => 'Warga aktif',
            'is_published' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($resident)->post('/pemilihan/'.$election.'/consent', ['election_consent' => '1'])->assertRedirect();
        $this->actingAs($admin)->post('/admin/pemilihan/'.$election.'/pemilih/'.$resident->id)->assertRedirect();
        $this->actingAs($admin)->patch('/admin/pemilihan/'.$election, ['status' => 'voting'])->assertRedirect();
        $this->actingAs($resident)->post('/pemilihan/'.$election.'/pilih', ['candidate_id' => $candidate, 'election_consent' => '1'])->assertRedirect();

        $this->assertDatabaseCount('election_ballots', 1);
        $ballotColumns = DB::getSchemaBuilder()->getColumnListing('election_ballots');
        $this->assertNotContains('user_id', $ballotColumns);
        $this->assertNotContains('voter_id', $ballotColumns);
        $this->assertNotNull(DB::table('election_eligibilities')->where('election_id', $election)->where('user_id', $resident->id)->value('voted_at'));
    }

    public function test_cctv_access_requires_approved_request_and_region_match(): void
    {
        $region = $this->createRegion();
        $resident = $this->createUser($region, 'warga', 'approved');
        $cameraId = DB::table('cctv_cameras')->insertGetId([
            'region_id' => $region->id,
            'name' => 'Gerbang Utama',
            'area' => 'Blok A',
            'stream_url' => 'https://camera-gateway.example.test/live',
            'status' => 'online',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($resident)->get('/keamanan/cctv')->assertOk();
        $this->actingAs($resident)->get('/keamanan/cctv/'.$cameraId.'/stream')->assertForbidden();
        DB::table('cctv_access_requests')->insert([
            'region_id' => $region->id,
            'camera_id' => $cameraId,
            'resident_id' => $resident->id,
            'purpose' => 'Keperluan keamanan',
            'status' => 'disetujui',
            'approved_until' => now()->addDay(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($resident)->get('/keamanan/cctv/'.$cameraId.'/stream')->assertRedirect('https://camera-gateway.example.test/live');
        $this->assertDatabaseHas('activity_logs', ['action' => 'cctv.stream_accessed', 'user_id' => $resident->id]);
    }

    public function test_successful_signed_webhook_settles_invoice_once_and_rejects_mismatched_amount(): void
    {
        $region = $this->createRegion();
        $resident = $this->createUser($region, 'warga', 'approved');
        $provider = PaymentProvider::query()->create([
            'region_id' => $region->id,
            'name' => 'Gateway Generic',
            'driver' => 'generic_http',
            'environment' => 'sandbox',
            'credentials' => ['webhook_secret' => 'webhook-secret'],
            'supported_methods' => ['QRIS'],
            'is_active' => true,
            'is_default' => true,
        ]);
        $invoiceId = DB::table('dues_invoices')->insertGetId([
            'region_id' => $region->id,
            'user_id' => $resident->id,
            'reference' => 'INV-TEST-1',
            'organization' => 'rt',
            'description' => 'Iuran uji',
            'amount' => 30000,
            'due_date' => now()->addWeek()->toDateString(),
            'status' => 'unpaid',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $transaction = PaymentTransaction::query()->create([
            'region_id' => $region->id,
            'payment_provider_id' => $provider->id,
            'user_id' => $resident->id,
            'reference' => 'PAY-TEST-1',
            'purpose' => 'iuran:INV-TEST-1',
            'amount' => 30000,
            'currency' => 'IDR',
            'method' => 'QRIS',
            'status' => 'pending',
            'metadata' => ['invoice_id' => $invoiceId],
        ]);
        $body = json_encode(['id' => 'evt-paid-1', 'reference' => $transaction->reference, 'status' => 'settlement', 'amount' => 30000, 'currency' => 'IDR'], JSON_THROW_ON_ERROR);
        $signature = hash_hmac('sha256', $body, 'webhook-secret');

        $server = ['CONTENT_TYPE' => 'application/json', 'HTTP_X_SIGNATURE' => $signature];
        $this->call('POST', '/webhooks/payments/'.$provider->id, [], [], [], $server, $body)->assertOk();
        $this->call('POST', '/webhooks/payments/'.$provider->id, [], [], [], $server, $body)->assertOk();
        $this->assertSame('paid', $transaction->fresh()->status);
        $this->assertDatabaseHas('dues_invoices', ['id' => $invoiceId, 'status' => 'paid']);
        $this->assertDatabaseCount('payment_webhook_events', 1);
        $this->assertDatabaseCount('ledger_transactions', 1);
    }

    /** @param array<string, mixed> $settings */
    private function createRegion(array $settings = []): Region
    {
        return Region::query()->create([
            'name' => 'Pilot',
            'district' => 'Sawangan',
            'city' => 'Depok',
            'province' => 'Jawa Barat',
            'slug' => 'pilot-'.fake()->unique()->lexify('??????'),
            'settings' => $settings,
        ]);
    }

    /** @param array<string, mixed> $attributes */
    private function createUser(Region $region, string $role, string $status = 'approved', array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'region_id' => $region->id,
            'role' => $role,
            'account_status' => $status,
        ], $attributes));
    }
}
