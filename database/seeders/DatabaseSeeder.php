<?php

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\Region;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $region = Region::query()->firstOrCreate(
            ['slug' => 'rt06-rw09-pengasinan'],
            [
                'name' => 'RT 06 / RW 09',
                'neighborhood' => 'Kelurahan Pengasinan',
                'district' => 'Sawangan',
                'city' => 'Depok',
                'province' => 'Jawa Barat',
                'settings' => ['expense_approval_limit' => 500000],
            ],
        );

        Announcement::query()->firstOrCreate(
            ['region_id' => $region->id, 'title' => 'Selamat datang di portal warga'],
            [
                'category' => 'kegiatan',
                'body' => 'Portal informasi dan layanan warga RT 06 / RW 09, Kelurahan Pengasinan, Kecamatan Sawangan, Kota Depok. Informasi wilayah dapat diperbarui pengurus setelah data lengkap.',
                'is_published' => true,
                'published_at' => now(),
            ],
        );

    }
}
