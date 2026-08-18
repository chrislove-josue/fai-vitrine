<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Customer;

/**
 * Résout le client rattaché à l'utilisateur authentifié.
 *
 * Toutes les ressources de l'espace client doivent être scopées par le
 * customer_uuid de l'utilisateur : c'est le point de contrôle anti-IDOR.
 */
trait ResolvesCustomer
{
    protected function clientCustomer(): ?Customer
    {
        $user = auth()->user();

        if ($user === null || $user->customer_uuid === null) {
            return null;
        }

        return Customer::where('uuid', $user->customer_uuid)->first();
    }

    protected function clientCustomerOrFail(): Customer
    {
        $customer = $this->clientCustomer();

        abort_unless($customer !== null, 404);

        return $customer;
    }
}
