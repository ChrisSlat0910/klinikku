<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // Queue
            'queue.view', 'queue.register', 'queue.call', 'queue.complete', 'queue.transfer',
            // RME
            'rme.view', 'rme.create', 'rme.edit',
            // Prescription
            'prescription.view', 'prescription.create', 'prescription.dispense',
            // Referral
            'referral.view', 'referral.create',
            // Lab
            'lab.view', 'lab.create', 'lab.result',
            // Billing
            'billing.view', 'billing.process',
            // Pharmacy
            'pharmacy.view', 'pharmacy.manage',
            // Reports
            'report.view', 'report.export',
            // Staff
            'staff.view', 'staff.create', 'staff.edit', 'staff.delete',
            // Roles
            'role.view', 'role.create', 'role.edit', 'role.delete', 'role.assign',
            // Settings
            'settings.view', 'settings.edit',
            // AI
            'ai.use',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Default role presets
        $roles = [
            'dokter' => [
                'queue.view', 'queue.call', 'queue.complete',
                'rme.view', 'rme.create', 'rme.edit',
                'prescription.view', 'prescription.create',
                'referral.view', 'referral.create',
                'lab.view', 'lab.create',
                'ai.use',
            ],
            'dokter_pj' => [
                'queue.view', 'queue.call', 'queue.complete', 'queue.transfer',
                'rme.view', 'rme.create', 'rme.edit',
                'prescription.view', 'prescription.create',
                'referral.view', 'referral.create',
                'lab.view', 'lab.create',
                'report.view',
                'staff.view',
                'ai.use',
            ],
            'perawat' => [
                'queue.view', 'queue.register', 'queue.call', 'queue.transfer',
                'rme.view',
            ],
            'apoteker' => [
                'pharmacy.view', 'pharmacy.manage',
                'prescription.view', 'prescription.dispense',
            ],
            'kasir' => [
                'billing.view', 'billing.process',
                'queue.view',
            ],
            'analis' => [
                'lab.view', 'lab.result',
            ],
            'admin' => [
                'staff.view', 'staff.create', 'staff.edit',
                'role.view', 'role.create', 'role.edit', 'role.assign',
                'settings.view', 'settings.edit',
                'report.view', 'report.export',
                'queue.view',
            ],
            'owner' => [
                'queue.view', 'queue.register', 'queue.call', 'queue.complete', 'queue.transfer',
                'rme.view',
                'prescription.view',
                'billing.view', 'billing.process',
                'pharmacy.view', 'pharmacy.manage',
                'report.view', 'report.export',
                'staff.view', 'staff.create', 'staff.edit', 'staff.delete',
                'role.view', 'role.create', 'role.edit', 'role.delete', 'role.assign',
                'settings.view', 'settings.edit',
                'ai.use',
            ],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($rolePermissions);
        }
    }
}
