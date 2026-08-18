<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Concerns\ResolvesCustomer;
use App\Models\NetworkSession;
use Illuminate\View\View;

class SessionController
{
    use ResolvesCustomer;

    public function index(): View
    {
        $customer = $this->clientCustomer();

        $accountUuids = $customer !== null ? $customer->networkAccounts()->pluck('uuid') : collect();

        $sessions = collect();
        $summary = ['bytes_in' => 0, 'bytes_out' => 0, 'active' => 0, 'total' => 0];

        if ($accountUuids->isNotEmpty()) {
            $sessions = NetworkSession::whereIn('network_account_uuid', $accountUuids)
                ->orderByDesc('started_at')
                ->limit(100)
                ->get();

            $summary = [
                'bytes_in' => (int) NetworkSession::whereIn('network_account_uuid', $accountUuids)->sum('bytes_in'),
                'bytes_out' => (int) NetworkSession::whereIn('network_account_uuid', $accountUuids)->sum('bytes_out'),
                'active' => NetworkSession::whereIn('network_account_uuid', $accountUuids)->whereNull('ended_at')->count(),
                'total' => NetworkSession::whereIn('network_account_uuid', $accountUuids)->count(),
            ];
        }

        return view('espace.sessions.index', [
            'customer' => $customer,
            'sessions' => $sessions,
            'summary' => $summary,
        ]);
    }
}
