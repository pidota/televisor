<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Setting;
use Illuminate\Database\Seeder;

class SystemDefaultsSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'admin', 'label' => 'Administrador'],
            ['name' => 'operator', 'label' => 'Operador'],
            ['name' => 'viewer', 'label' => 'Solo lectura'],
        ];

        foreach ($roles as $role) {
            Role::query()->firstOrCreate(['name' => $role['name']], $role);
        }

        $settings = [
            'app.timezone' => 'America/Santiago',
            'device.heartbeat_interval_seconds' => '60',
            'device.manifest_poll_seconds' => '120',
            'device.offline_threshold_seconds' => '180',
            'pairing.code_ttl_minutes' => '15',
            'media.max_upload_mb' => '512',
        ];

        foreach ($settings as $key => $value) {
            Setting::query()->firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
