<?php

namespace App\Console\Commands;

use App\Models\DeviceHeartbeat;
use Illuminate\Console\Command;

class PruneDeviceHeartbeatsCommand extends Command
{
    protected $signature = 'televisor:prune-heartbeats {--days=30 : Días de retención}';

    protected $description = 'Elimina registros antiguos de device_heartbeats';

    public function handle(): int
    {
        $days = max((int) $this->option('days'), 7);
        $cutoff = now()->subDays($days);

        $deleted = DeviceHeartbeat::query()
            ->where('created_at', '<', $cutoff)
            ->delete();

        $this->info("Heartbeats eliminados: {$deleted} (anteriores a {$cutoff->toDateString()})");

        return self::SUCCESS;
    }
}
