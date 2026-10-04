<?php

namespace Tests\Feature;

use App\Models\OrganizationMember;
use App\Models\Region;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OrganizationFeatureTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<string> */
    private array $zipFilesBeforeTest = [];

    public function test_backup_command_creates_zip_and_respects_retention(): void
    {
        $this->createRegion();
        $dir = str_replace('\\', '/', storage_path('app/backups'));

        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $this->zipFilesBeforeTest = glob($dir.'/*.zip') ?: [];

        // Seed 15 backup lama agar retensi (maksimal 14 harian) bisa diuji.
        foreach (range(1, 15) as $i) {
            touch($dir.'/ruang-warga-daily-20200101-'.str_pad((string) $i, 6, '0', STR_PAD_LEFT).'.zip');
        }

        $initialCount = count(glob($dir.'/ruang-warga-daily-*.zip') ?: []);
        $this->assertSame(15, $initialCount);

        $exitCode = Artisan::call('app:backup', ['--type' => 'daily']);
        $this->assertSame(0, $exitCode);

        // 15 zip lama + 1 zip baru = 16; retensi (14 harian) seharusnya menyisakan 14.
        $this->assertCount(14, glob($dir.'/ruang-warga-daily-*.zip') ?: []);
        $this->assertNotEmpty(glob($dir.'/ruang-warga-daily-'.now()->format('Ymd').'*.zip') ?: [], 'Backup file for today was not created.');
    }

    protected function tearDown(): void
    {
        $dir = str_replace('\\', '/', storage_path('app/backups'));

        foreach (glob($dir.'/*.zip') ?: [] as $file) {
            if (! in_array($file, $this->zipFilesBeforeTest, true)) {
                @unlink($file);
            }
        }

        foreach (glob($dir.'/database-dump-*.sql') ?: [] as $file) {
            @unlink($file);
        }

        parent::tearDown();
    }

    public function test_public_organization_page_loads_and_lists_members(): void
    {
        $region = $this->createRegion();
        OrganizationMember::query()->create([
            'region_id' => $region->id,
            'organization_type' => 'rt',
            'name' => 'Budi Santoso',
            'position' => 'Ketua RT',
            'term_starts_at' => now()->subYear()->toDateString(),
            'term_ends_at' => now()->addYears(2)->toDateString(),
        ]);

        $this->get('/struktur-organisasi')->assertOk()->assertSee('Budi Santoso')->assertSee('Ketua RT');
    }

    public function test_admin_can_create_and_delete_organization_members(): void
    {
        Storage::fake('public');
        $region = $this->createRegion();
        $admin = User::factory()->create(['role' => 'admin_rt', 'account_status' => 'approved', 'region_id' => $region->id]);
        $resident = User::factory()->create(['role' => 'warga', 'account_status' => 'approved', 'region_id' => $region->id]);

        $this->actingAs($resident)->get('/admin/struktur-organisasi')->assertForbidden();

        $this->actingAs($admin)->post('/admin/struktur-organisasi', [
            'organization_type' => 'dkm',
            'name' => 'Ahmad Fauzi',
            'position' => 'Ketua DKM',
            'photo' => UploadedFile::fake()->image('foto.jpg'),
            'display_order' => 1,
        ])->assertRedirect('/admin/struktur-organisasi');

        $member = OrganizationMember::query()->firstOrFail();
        $this->assertSame('dkm', $member->organization_type);
        $this->assertSame('Ahmad Fauzi', $member->name);
        Storage::disk('public')->assertExists($member->photo_path);

        $this->assertDatabaseHas('activity_logs', ['action' => 'organization.member_created', 'subject_id' => $member->id]);

        $this->actingAs($admin)->delete('/admin/struktur-organisasi/'.$member->id)->assertRedirect('/admin/struktur-organisasi');
        $this->assertDatabaseMissing('organization_members', ['id' => $member->id]);
    }

    public function test_organization_member_cannot_be_modified_across_regions(): void
    {
        $region = $this->createRegion();
        $otherRegion = Region::query()->create(['name' => 'Wilayah Lain', 'district' => 'Beji', 'city' => 'Depok', 'province' => 'Jawa Barat', 'slug' => 'wilayah-lain-tes']);
        $admin = User::factory()->create(['role' => 'admin_rt', 'account_status' => 'approved', 'region_id' => $region->id]);
        $member = OrganizationMember::query()->create([
            'region_id' => $otherRegion->id,
            'organization_type' => 'rt',
            'name' => 'Orang Lain',
            'position' => 'Ketua RT',
        ]);

        $this->actingAs($admin)->delete('/admin/struktur-organisasi/'.$member->id)->assertNotFound();
        $this->assertDatabaseHas('organization_members', ['id' => $member->id]);
    }

    private function createRegion(): Region
    {
        return Region::query()->create([
            'name' => 'RT 06 / RW 09',
            'neighborhood' => 'Kelurahan Pengasinan',
            'district' => 'Sawangan',
            'city' => 'Depok',
            'province' => 'Jawa Barat',
            'slug' => 'pengasinan-'.fake()->unique()->lexify('??????'),
            'settings' => ['expense_approval_limit' => 500000],
        ]);
    }
}
