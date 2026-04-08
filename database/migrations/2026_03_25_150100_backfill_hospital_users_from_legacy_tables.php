<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('hospital_users')) {
            return;
        }

        $roleMap = DB::table('roles')
            ->select('id', 'tenant_id', 'slug')
            ->get()
            ->groupBy('tenant_id');

        $now = now();

        $legacyAdmins = DB::table('hospital_admins')
            ->select('id', 'tenant_id', 'name', 'email', 'password', 'contact', 'status', 'last_login_at', 'remember_token', 'created_at', 'updated_at', 'deleted_at')
            ->get();

        foreach ($legacyAdmins as $admin) {
            $exists = DB::table('hospital_users')
                ->where('tenant_id', $admin->tenant_id)
                ->where('email', $admin->email)
                ->exists();

            if ($exists) {
                continue;
            }

            $roleId = optional($roleMap->get($admin->tenant_id))->firstWhere('slug', 'hospital_admin')->id ?? null;

            DB::table('hospital_users')->insert([
                'tenant_id' => $admin->tenant_id,
                'role_id' => $roleId,
                'name' => $admin->name,
                'email' => $admin->email,
                'password' => $admin->password,
                'contact' => $admin->contact,
                'status' => $admin->status ?? 'active',
                'doctor_type' => null,
                'foc_permission' => false,
                'last_login_at' => $admin->last_login_at,
                'remember_token' => $admin->remember_token,
                'created_at' => $admin->created_at ?? $now,
                'updated_at' => $admin->updated_at ?? $now,
                'deleted_at' => $admin->deleted_at,
            ]);
        }

        if (Schema::hasTable('doctors')) {
            $legacyDoctors = DB::table('doctors')
                ->select('tenant_id', 'name', 'email', 'password', 'contact', 'type', 'foc_permission', 'status', 'last_login_at', 'remember_token', 'created_at', 'updated_at', 'deleted_at')
                ->get();

            foreach ($legacyDoctors as $doctor) {
                $exists = DB::table('hospital_users')
                    ->where('tenant_id', $doctor->tenant_id)
                    ->where('email', $doctor->email)
                    ->exists();

                if ($exists) {
                    continue;
                }

                $roleId = optional($roleMap->get($doctor->tenant_id))->firstWhere('slug', 'doctor')->id ?? null;

                DB::table('hospital_users')->insert([
                    'tenant_id' => $doctor->tenant_id,
                    'role_id' => $roleId,
                    'name' => $doctor->name,
                    'email' => $doctor->email,
                    'password' => $doctor->password,
                    'contact' => $doctor->contact,
                    'status' => $doctor->status ?? 'active',
                    'doctor_type' => in_array($doctor->type, ['primary', 'secondary'], true) ? $doctor->type : null,
                    'foc_permission' => (bool) $doctor->foc_permission,
                    'last_login_at' => $doctor->last_login_at,
                    'remember_token' => $doctor->remember_token,
                    'created_at' => $doctor->created_at ?? $now,
                    'updated_at' => $doctor->updated_at ?? $now,
                    'deleted_at' => $doctor->deleted_at,
                ]);
            }
        }

        if (Schema::hasTable('receptions')) {
            $legacyReceptions = DB::table('receptions')
                ->select('tenant_id', 'name', 'email', 'password', 'contact', 'status', 'last_login_at', 'remember_token', 'created_at', 'updated_at', 'deleted_at')
                ->get();

            foreach ($legacyReceptions as $reception) {
                $exists = DB::table('hospital_users')
                    ->where('tenant_id', $reception->tenant_id)
                    ->where('email', $reception->email)
                    ->exists();

                if ($exists) {
                    continue;
                }

                $roleId = optional($roleMap->get($reception->tenant_id))->firstWhere('slug', 'reception')->id ?? null;

                DB::table('hospital_users')->insert([
                    'tenant_id' => $reception->tenant_id,
                    'role_id' => $roleId,
                    'name' => $reception->name,
                    'email' => $reception->email,
                    'password' => $reception->password,
                    'contact' => $reception->contact,
                    'status' => $reception->status ?? 'active',
                    'doctor_type' => null,
                    'foc_permission' => false,
                    'last_login_at' => $reception->last_login_at,
                    'remember_token' => $reception->remember_token,
                    'created_at' => $reception->created_at ?? $now,
                    'updated_at' => $reception->updated_at ?? $now,
                    'deleted_at' => $reception->deleted_at,
                ]);
            }
        }

        if (Schema::hasTable('ot_staff')) {
            $legacyOtStaff = DB::table('ot_staff')
                ->select('tenant_id', 'name', 'email', 'password', 'contact', 'type', 'status', 'last_login_at', 'remember_token', 'created_at', 'updated_at', 'deleted_at')
                ->get();

            $typeToSlug = [
                'ot_receptionist' => 'ot_receptionist',
                'accountant' => 'accountant',
                'ot_doctor' => 'ot_doctor',
                'ot_assistant' => 'ot_assistant',
            ];

            foreach ($legacyOtStaff as $otUser) {
                $exists = DB::table('hospital_users')
                    ->where('tenant_id', $otUser->tenant_id)
                    ->where('email', $otUser->email)
                    ->exists();

                if ($exists) {
                    continue;
                }

                $slug = $typeToSlug[$otUser->type] ?? null;
                $roleId = $slug ? (optional($roleMap->get($otUser->tenant_id))->firstWhere('slug', $slug)->id ?? null) : null;

                DB::table('hospital_users')->insert([
                    'tenant_id' => $otUser->tenant_id,
                    'role_id' => $roleId,
                    'name' => $otUser->name,
                    'email' => $otUser->email,
                    'password' => $otUser->password,
                    'contact' => $otUser->contact,
                    'status' => $otUser->status ?? 'active',
                    'doctor_type' => null,
                    'foc_permission' => false,
                    'last_login_at' => $otUser->last_login_at,
                    'remember_token' => $otUser->remember_token,
                    'created_at' => $otUser->created_at ?? $now,
                    'updated_at' => $otUser->updated_at ?? $now,
                    'deleted_at' => $otUser->deleted_at,
                ]);
            }
        }
    }

    public function down(): void
    {
        // Intentionally no destructive rollback for data backfill.
    }
};
