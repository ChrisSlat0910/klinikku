<?php

namespace Database\Seeders;

use App\Models\Clinic;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $praktek = Clinic::where('slug', 'praktek-dr-budi')->first();
        $pratama = Clinic::where('slug', 'klinik-sehat-bersama')->first();
        $utama = Clinic::where('slug', 'klinik-spesialis-ananda')->first();

        // Praktek Mandiri — Owner/Dokter
        $this->createUser($praktek->id, 'dr. Budi Santoso', 'budi@praktek.demo', 'password', 'owner', 'dokter_pj');

        // Klinik Pratama
        $this->createUser($pratama->id, 'dr. Sari Dewi', 'sari@pratama.demo', 'password', 'owner');
        $this->createUser($pratama->id, 'dr. Andi Wijaya', 'andi@pratama.demo', 'password', 'dokter');
        $this->createUser($pratama->id, 'Ns. Rina Putri', 'rina@pratama.demo', 'password', 'perawat');
        $this->createUser($pratama->id, 'Apt. Dewi Lestari', 'dewi@pratama.demo', 'password', 'apoteker');
        $this->createUser($pratama->id, 'Budi Kasir', 'kasir@pratama.demo', 'password', 'kasir');
        $this->createUser($pratama->id, 'Ahmad Analis', 'analis@pratama.demo', 'password', 'analis');

        // Klinik Utama
        $this->createUser($utama->id, 'dr. Hendra Sp.PD', 'hendra@utama.demo', 'password', 'owner');
        $this->createUser($utama->id, 'dr. Maya Sp.OG', 'maya@utama.demo', 'password', 'dokter');
        $this->createUser($utama->id, 'Ns. Fitri', 'fitri@utama.demo', 'password', 'perawat');
        $this->createUser($utama->id, 'Apt. Rizky', 'rizky@utama.demo', 'password', 'apoteker');
        $this->createUser($utama->id, 'Admin Utama', 'admin@utama.demo', 'password', 'admin');
    }

    private function createUser(int $clinicId, string $name, string $email, string $password, string ...$roles): void
    {
        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'clinic_id' => $clinicId,
                'name' => $name,
                'password' => Hash::make($password),
                'status' => 'active',
            ]
        );

        $user->syncRoles($roles);
    }
}
