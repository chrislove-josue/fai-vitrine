@extends('layouts.app')

@section('title', 'Facture '.$invoice->invoice_number)

@section('content')
    <div class="page-head">
        <div>
            <p class="eyebrow">Espace client &middot; JENY SAS</p>
            <h1>Facture {{ $invoice->invoice_number }}</h1>
            <p class="page-sub">
                <span class="badge {{ $invoice->status }}">{{ $invoice->status }}</span>
                &nbsp;émise le {{ $invoice->issue_date?->format('d/m/Y') ?? '—' }}
            </p>
        </div>
        <a class="btn outline" href="{{ route('client.invoices.pdf', $invoice) }}">
            <svg width="14" height="14" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
            Télécharger le PDF
        </a>
    </div>

    <div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(200px,1fr))">
        <div class="stat">
            <div class="label">Statut</div>
            <div class="value" style="font-size:1.05rem"><span class="badge {{ $invoice->status }}">{{ $invoice->status }}</span></div>
        </div>
        <div class="stat">
            <div class="label">Émise le</div>
            <div class="value" style="font-size:1.05rem">{{ $invoice->issue_date?->format('d/m/Y') ?? '—' }}</div>
        </div>
        <div class="stat">
            <div class="label">Échéance</div>
            <div class="value" style="font-size:1.05rem">{{ $invoice->due_date?->format('d/m/Y') ?? '—' }}</div>
        </div>
        <div class="stat">
            <div class="label">Abonnement</div>
            <div class="value" style="font-size:1.05rem">{{ $invoice->subscription?->subscription_number ?? '—' }}</div>
        </div>
    </div>

    <div class="card">
        <h2><svg class="ic" width="16" height="16" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h12a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm6 2a1 1 0 100 2h4a1 1 0 100-2h-4zm-3 3a1 1 0 011-1h6a1 1 0 110 2H8a1 1 0 01-1-1zm1 3a1 1 0 100 2h4a1 1 0 100-2H8z" clip-rule="evenodd"/></svg>
            Détail de la facture</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Description</th>
                        <th>Qté</th>
                        <th>P.U.</th>
                        <th style="text-align:right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($invoice->items as $item)
                        <tr>
                            <td>{{ $item->description }}</td>
                            <td>{{ $item->quantity }}</td>
                            <td>{{ number_format($item->unit_price, 0, ',', ' ') }}</td>
                            <td style="text-align:right">{{ number_format($item->total, 0, ',', ' ') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <dl class="dl" style="margin-top:1.1rem">
            <dt>Sous-total</dt>
            <dd>{{ number_format($invoice->subtotal, 0, ',', ' ') }} {{ $invoice->currency }}</dd>
            <dt>Total</dt>
            <dd class="amount">{{ number_format($invoice->total, 0, ',', ' ') }} {{ $invoice->currency }}</dd>
            <dt>Déjà payé</dt>
            <dd>{{ number_format($invoice->amount_paid, 0, ',', ' ') }} {{ $invoice->currency }}</dd>
            <dt>Restant dû</dt>
            <dd class="amount accent">{{ number_format($invoice->amount_due, 0, ',', ' ') }} {{ $invoice->currency }}</dd>
        </dl>

        @if ($invoice->amount_due > 0 && ! in_array($invoice->status, ['paid', 'cancelled', 'refunded']))
            <div style="margin-top:1.1rem;padding-top:1.1rem;border-top:1px solid #eef0f4;display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap">
                <p class="muted" style="margin:0;font-size:.78rem;max-width:26rem">
                    Le paiement est confirmé par notre prestataire. Une fois validé, votre facture
                    sera soldée et votre abonnement réactivé automatiquement.
                </p>
                <form method="POST" action="{{ route('client.payments.store', $invoice) }}">
                    @csrf
                    <button class="btn" type="submit">
                        <svg width="14" height="14" viewBox="0 0 20 20" fill="currentColor"><path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4zm14 5H2v6a2 2 0 002 2h12a2 2 0 002-2V9zM5 13a1 1 0 011-1h1a1 1 0 110 2H6a1 1 0 01-1-1z"/></svg>
                        Payer {{ number_format($invoice->amount_due, 0, ',', ' ') }} {{ $invoice->currency }}
                    </button>
                </form>
            </div>
        @endif
    </div>

    @if ($invoice->payments->isNotEmpty())
        <div class="card">
            <h2><svg class="ic" width="16" height="16" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V7.414A2 2 0 0015.414 6L12 2.586A2 2 0 0010.586 2H6zm2 10a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1zm0 3a1 1 0 011-1h4a1 1 0 110 2H9a1 1 0 01-1-1z" clip-rule="evenodd"/></svg>
                Historique des paiements</h2>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Référence</th>
                            <th>Montant</th>
                            <th>Statut</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($invoice->payments as $payment)
                            <tr>
                                <td><strong>{{ $payment->payment_reference }}</strong></td>
                                <td>{{ number_format($payment->amount, 0, ',', ' ') }} {{ $payment->currency }}</td>
                                <td><span class="badge {{ $payment->status }}">{{ $payment->status }}</span></td>
                                <td>{{ $payment->paid_at?->format('d/m/Y H:i') ?? $payment->created_at->format('d/m/Y H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection