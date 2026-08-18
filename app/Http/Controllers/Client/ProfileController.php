<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Concerns\ResolvesCustomer;
use Illuminate\View\View;

class ProfileController
{
    use ResolvesCustomer;

    public function show(): View
    {
        $customer = $this->clientCustomerOrFail();

        $customer->load(['contacts', 'addresses', 'documents']);

        return view('espace.profile.show', ['customer' => $customer]);
    }
}
