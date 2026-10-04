<?php

namespace App\Console\Commands;

use App\Models\Announcement;
use App\Models\Region;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

#[Signature('app:create-superadmin {email} {--name=Superadmin}')]
#[Description('Create the first approved superadmin account and prompt for its password')]
class CreateSuperadmin extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (User::query()->where('role', 'superadmin')->exists()) {
            $this->components->error('Superadmin already exists.');

            return self::FAILURE;
        }

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
            ['category' => 'kegiatan', 'body' => 'Portal informasi dan layanan warga RT 06 / RW 09, Kelurahan Pengasinan, Kecamatan Sawangan, Kota Depok.', 'is_published' => true, 'published_at' => now()],
        );

        $password = $this->secret('Superadmin password (minimum 12 characters)');

        if (! is_string($password) || strlen($password) < 12) {
            $this->components->error('Password must contain at least 12 characters.');

            return self::FAILURE;
        }

        $user = User::query()->create([
            'name' => $this->option('name'),
            'email' => $this->argument('email'),
            'password' => Hash::make($password),
            'role' => 'superadmin',
            'account_status' => 'approved',
            'region_id' => $region->id,
            'consented_at' => now(),
        ]);

        $this->components->info("Superadmin {$user->email} created.");

        return self::SUCCESS;
    }
}
