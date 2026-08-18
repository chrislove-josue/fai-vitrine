<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * Rattache un utilisateur existant à un compte client (users.customer_uuid → customers.uuid).
 */
class LinkCustomerCommand extends Command
{
    protected $signature = 'user:link-customer {email : Email de l\'utilisateur}
        {--customer-uuid= : UUID du client (isp_core.customers.uuid)}
        {--customer-number= : Numéro client (ex. CUS-DEMO001) pour résoudre l\'uuid automatiquement}';

    protected $description = 'Rattache un utilisateur à un compte client';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();

        if ($user === null) {
            $this->error("Utilisateur {$this->argument('email')} introuvable.");

            return self::FAILURE;
        }

        $customerUuid = $this->option('customer-uuid') ?: null;
        $customerNumber = $this->option('customer-number') ?: null;

        if ($customerUuid === null && $customerNumber === null) {
            $this->error('Fournissez --customer-uuid OU --customer-number.');

            return self::FAILURE;
        }

        if ($customerUuid !== null && $customerNumber !== null) {
            $this->error('Fournissez --customer-uuid OU --customer-number, pas les deux.');

            return self::FAILURE;
        }

        if ($customerNumber !== null) {
            $customer = Customer::where('customer_number', $customerNumber)->first();

            if ($customer === null) {
                $this->error("Aucun client avec le numéro {$customerNumber}.");

                return self::FAILURE;
            }

            $customerUuid = $customer->uuid;
        }

        if (Customer::where('uuid', $customerUuid)->doesntExist()) {
            $this->error("Aucun client avec l'uuid {$customerUuid}.");

            return self::FAILURE;
        }

        $user->update(['customer_uuid' => $customerUuid]);

        $this->info("Utilisateur {$user->email} rattaché au client {$customerUuid}.");

        return self::SUCCESS;
    }
}
