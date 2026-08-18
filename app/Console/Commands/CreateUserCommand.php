<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

/**
 * Création d'un utilisateur avec un rôle (admin, client, finance, ...).
 */
class CreateUserCommand extends Command
{
    protected $signature = 'user:create
        {--name= : Nom complet}
        {--email= : Adresse email (unique)}
        {--password= : Mot de passe}
        {--role=client : Rôle (super_admin|admin|finance|commercial|support|network_admin|operator|client)}
        {--customer-uuid= : UUID du client (isp_core.customers.uuid) si rôle client}
        {--customer-number= : Numéro client (ex. CUS-DEMO001) pour résoudre l\'uuid automatiquement}';

    protected $description = 'Crée un utilisateur avec mot de passe haché et rôle';

    public function handle(): int
    {
        $name = $this->option('name') ?: $this->ask('Nom complet');
        $email = $this->option('email') ?: $this->ask('Email');
        $password = $this->option('password') ?: $this->secret('Mot de passe');
        $role = $this->option('role');
        $customerUuid = $this->option('customer-uuid') ?: null;
        $customerNumber = $this->option('customer-number') ?: null;

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

        if ($customerUuid !== null && Customer::where('uuid', $customerUuid)->doesntExist()) {
            $this->error("Aucun client avec l'uuid {$customerUuid}.");

            return self::FAILURE;
        }

        try {
            $data = validator([
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'role' => $role,
                'customer_uuid' => $customerUuid,
            ], [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', Rule::unique('users', 'email')->whereNull('deleted_at')],
                'password' => ['required', 'string', 'min:8'],
                'role' => ['required', Rule::in(Role::pluck('name')->all())],
                'customer_uuid' => ['nullable', 'uuid'],
            ])->validate();
        } catch (ValidationException $e) {
            foreach ($e->errors() as $errors) {
                $this->error($errors[0]);
            }

            return self::FAILURE;
        }

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'customer_uuid' => $data['customer_uuid'],
            'status' => User::STATUS_ACTIVE,
        ]);

        $user->assignRole($data['role']);

        $this->info("Utilisateur {$user->email} créé avec le rôle {$data['role']}.");

        if ($data['role'] === 'client' && $data['customer_uuid'] === null) {
            $this->warn("Le rôle est « client » mais aucun client n'est rattaché. Utilisez user:link-customer {$user->email} --customer-number=CUS-XXXX.");
        }

        return self::SUCCESS;
    }
}
