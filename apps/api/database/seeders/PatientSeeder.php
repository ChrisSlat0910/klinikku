<?php

namespace Database\Seeders;

use App\Models\Clinic;
use App\Models\Patient;
use Illuminate\Database\Seeder;

class PatientSeeder extends Seeder
{
    public function run(): void
    {
        $clinics = Clinic::all();

        $patients = [
            ['name' => 'Agus Setiawan', 'phone' => '08111234567', 'nik' => '7371012501900001', 'gender' => 'M', 'dob' => '1990-01-25'],
            ['name' => 'Siti Rahayu', 'phone' => '08222345678', 'nik' => '7371015506950002', 'gender' => 'F', 'dob' => '1995-06-15'],
            ['name' => 'Budi Hartono', 'phone' => '08333456789', 'nik' => '7371011203880003', 'gender' => 'M', 'dob' => '1988-03-12'],
            ['name' => 'Dewi Kusuma', 'phone' => '08444567890', 'nik' => '7371016607920004', 'gender' => 'F', 'dob' => '1992-07-26'],
            ['name' => 'Rudi Santoso', 'phone' => '08555678901', 'nik' => '7371010909850005', 'gender' => 'M', 'dob' => '1985-09-09'],
        ];

        foreach ($clinics as $clinic) {
            foreach ($patients as $patient) {
                Patient::firstOrCreate(
                    ['clinic_id' => $clinic->id, 'nik' => $patient['nik']],
                    array_merge($patient, ['clinic_id' => $clinic->id])
                );
            }
        }
    }
}
