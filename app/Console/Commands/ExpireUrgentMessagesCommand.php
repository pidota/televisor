<?php

namespace App\Console\Commands;

use App\Services\UrgentMessageService;
use Illuminate\Console\Command;

class ExpireUrgentMessagesCommand extends Command
{
    protected $signature = 'urgent-messages:expire';

    protected $description = 'Marca como expirados los mensajes urgentes activos fuera de vigencia y actualiza manifiestos';

    public function handle(UrgentMessageService $urgentMessages): int
    {
        $count = $urgentMessages->expireDue();

        if ($count > 0) {
            $this->info("Mensajes expirados: {$count}");
        }

        return self::SUCCESS;
    }
}
