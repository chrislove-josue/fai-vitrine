@extends('layouts.app')

@section('title', 'Mes paiements')

@section('content')
    <div class="page-head">
        <div>
            <p class="eyebrow">Espace client &middot; JENY SAS</p>
            <h1>Mes paiements</h1>
            <p class="page-sub">Suivez l'état de vos paiements par Mobile Money et leurs confirmations.</p>
        </div>
    </div>

    @if ($customer === null)
        <div class="card">
            <p class="muted">Aucun compte client n'est rattaché à cet utilisateur.</p>
        </div>
    @elseif ($payments->isEmpty())
        <div class="card">
            <p class="muted">Aucun paiement.</p>
        </div>
    @else
        <div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(200px,1fr))">
            <div class="stat">
                <div class="label">Paiements</div>
                <div class="value">{{ $payments->count() }}</div>
            </div>
            <div class="stat">
                <div class="label">Réussis</div>
                <div class="value green">{{ $payments->where('status', 'successful')->count() }}</div>
            </div>
            <div class="stat">
                <div class="label">En attente</div>
                <div class="value gold">{{ $payments->whereIn('status', ['pending', 'processing'])->count() }}</div>
            </div>
            <div class="stat">
                <div class="label">Total payé</div>
                <div class="value" style="font-size:1.05rem">
                    {{ number_format($payments->where('status', 'successful')->sum('amount'), 0, ',', ' ') }} XOF
                </div>
            </div>
        </div>

        <div class="card">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Référence</th>
                            <th>Facture</th>
                            <th>Montant</th>
                            <th>Statut</th>
                            <th>Demandé le</th>
                            <th>Payé le</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($payments as $payment)
                            <tr>
                                <td><strong>{{ $payment->payment_reference }}</strong></td>
                                <td>
                                    @if ($payment->invoice)
                                        <a href="{{ route('client.invoices.show', $payment->invoice) }}" style="color:var(--jeny-primary);font-weight:700">{{ $payment->invoice->invoice_number }}</a>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="amount">{{ number_format($payment->amount, 0, ',', ' ') }} {{ $payment->currency }}</td>
                                <td><span class="badge {{ $payment->status }}">{{ $payment->status }}</span></td>
                                <td>{{ $payment->created_at->format('d/m/Y H:i') }}</td>
                                <td>{{ $payment->paid_at?->format('d/m/Y H:i') ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection