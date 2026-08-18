<?php

namespace App\Console\Commands;

use App\Services\RadiusSyncService;
use Illuminate\Console\Command;

class ProcessOutboxCommand extends Command
{
    protected $signature = 'radius:sync {--limit=10 : Nombre d\'événements outbox à traiter}';

    protected $description = 'Traite les événements outbox en attente et synchronise FreeRADIUS';

    public function handle(RadiusSyncService $syncService): int
    {
        $result = $syncService->processPending((int) $this->option('limit'));

        $this->info(sprintf(
            'Synchronisation terminée : %d traités, %d en échec.',
            $result['processed'],
            $result['failed'],
        ));

        return $result['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
