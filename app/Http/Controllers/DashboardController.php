<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesCustomer;
use App\Models\Invoice;
use App\Models\NetworkSession;
use App\Models\Payment;
use App\Models\Subscription;
use Illuminate\View\View;

class DashboardController
{
    use ResolvesCustomer;

    /**
     * Tableau de bord de l'espace client (étape 10).
     */
    public function client(): View
    {
        $customer = $this->clientCustomer();

        $subscriptions = collect();
        $invoices = collect();
        $lastPayment = null;
        $upcomingInvoices = collect();
        $sessionSummary = ['bytes_in' => 0, 'bytes_out' => 0, 'active' => 0];

        if ($customer !== null) {
            $subscriptions = Subscription::where('customer_id', $customer->id)->get();
            $invoices = Invoice::where('customer_id', $customer->id)->get();
            $lastPayment = Payment::where('customer_id', $customer->id)
                ->where('status', Payment::STATUS_SUCCESSFUL)
                ->orderByDesc('paid_at')
                ->first();
            $upcomingInvoices = Invoice::where('customer_id', $customer->id)
                ->whereIn('status', [Invoice::STATUS_ISSUED, Invoice::STATUS_PARTIALLY_PAID, Invoice::STATUS_OVERDUE])
                ->where('amount_due', '>', 0)
                ->orderBy('due_date')
                ->get();

            $accountUuids = $customer->networkAccounts()->pluck('uuid');

            if ($accountUuids->isNotEmpty()) {
                $sessionSummary = [
                    'bytes_in' => (int) NetworkSession::whereIn('network_account_uuid', $accountUuids)->sum('bytes_in'),
                    'bytes_out' => (int) NetworkSession::whereIn('network_account_uuid', $accountUuids)->sum('bytes_out'),
                    'active' => NetworkSession::whereIn('network_account_uuid', $accountUuids)->whereNull('ended_at')->count(),
                ];
            }
        }

        return view('dashboard.index', [
            'customer' => $customer,
            'subscriptions' => $subscriptions,
            'invoices' => $invoices,
            'lastPayment' => $lastPayment,
            'upcomingInvoices' => $upcomingInvoices,
            'sessionSummary' => $sessionSummary,
        ]);
    }
}
