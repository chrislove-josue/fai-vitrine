<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Concerns\ResolvesCustomer;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\View\View;

class InvoiceController
{
    use ResolvesCustomer;

    public function index(): View
    {
        $customer = $this->clientCustomer();

        $invoices = $customer !== null
            ? Invoice::where('customer_id', $customer->id)->orderByDesc('issue_date')->get()
            : collect();

        return view('espace.invoices.index', [
            'customer' => $customer,
            'invoices' => $invoices,
        ]);
    }

    public function show(Invoice $invoice): View
    {
        $customer = $this->clientCustomerOrFail();

        abort_unless($invoice->customer_id === $customer->id, 404);

        $invoice->load(['items', 'payments', 'subscription']);

        return view('espace.invoices.show', [
            'customer' => $customer,
            'invoice' => $invoice,
        ]);
    }

    public function pdf(Invoice $invoice): Response
    {
        $customer = $this->clientCustomerOrFail();

        abort_unless($invoice->customer_id === $customer->id, 404);

        $invoice->load(['items', 'customer']);

        $pdf = Pdf::loadView('pdf.invoice', ['invoice' => $invoice]);

        return $pdf->download('facture-'.$invoice->invoice_number.'.pdf');
    }
}
