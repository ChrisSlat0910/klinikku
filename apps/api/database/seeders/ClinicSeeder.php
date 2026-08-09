<?php

namespace Database\Seeders;

use App\Models\Clinic;
use Illuminate\Database\Seeder;

class ClinicSeeder extends Seeder
{
    public function run(): void
    {
        $clinics = [
            [
                'name' => 'Praktek dr. Budi Santoso',
                'slug' => 'praktek-dr-budi',
                'clinic_type' => 'praktek_mandiri',
                'phone' => '08112345678',
                'address' => 'Jl. Mawar No. 12, Manado, Sulawesi Utara',
                'wa_token' => 'demo-token-praktek-mandiri',
                'feature_flags' => [
                    'walk_in' => true,
                    'online_queue' => true,
                    'queue_appointment' => false,
                    'lab' => false,
                    'bpjs_reporting' => false,
                    'multi_poli' => false,
                ],
            ],
            [
                'name' => 'Klinik Sehat Bersama',
                'slug' => 'klinik-sehat-bersama',
                'clinic_type' => 'klinik_pratama',
                'phone' => '08223456789',
                'address' => 'Jl. Pahlawan No. 45, Makassar, Sulawesi Selatan',
                'wa_token' => 'demo-token-klinik-pratama',
                'feature_flags' => [
                    'walk_in' => true,
                    'online_queue' => true,
                    'queue_appointment' => true,
                    'lab' => true,
                    'bpjs_reporting' => true,
                    'multi_poli' => true,
                ],
            ],
            [
                'name' => 'Klinik Spesialis Ananda',
                'slug' => 'klinik-spesialis-ananda',
                'clinic_type' => 'klinik_utama',
                'phone' => '08334567890',
                'address' => 'Jl. Sudirman No. 88, Manado, Sulawesi Utara',
                'wa_token' => 'demo-token-klinik-utama',
                'feature_flags' => [
                    'walk_in' => false,
                    'online_queue' => true,
                    'queue_appointment' => true,
                    'lab' => true,
                    'bpjs_reporting' => true,
                    'multi_poli' => true,
                ],
            ],
        ];

        foreach ($clinics as $clinic) {
            Clinic::firstOrCreate(['slug' => $clinic['slug']], $clinic);
        }
    }
}
