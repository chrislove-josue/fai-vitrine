@extends('layouts.app')

@section('title', 'Mes factures')

@section('content')
    <div class="page-head">
        <div>
            <p class="eyebrow">Espace client &middot; JENY SAS</p>
            <h1>Mes factures</h1>
            <p class="page-sub">Retrouvez ici toutes vos factures, leur statut et le montant restant dû.</p>
        </div>
    </div>

    @if ($customer === null)
        <div class="card">
            <p class="muted">Aucun compte client n'est rattaché à cet utilisateur.</p>
        </div>
    @elseif ($invoices->isEmpty())
        <div class="card">
            <p class="muted">Aucune facture.</p>
        </div>
    @else
        <div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(200px,1fr))">
            <div class="stat">
                <div class="label">Factures émises</div>
                <div class="value">{{ $invoices->count() }}</div>
            </div>
            <div class="stat">
                <div class="label">Payées</div>
                <div class="value green">{{ $invoices->where('status', 'paid')->count() }}</div>
            </div>
            <div class="stat">
                <div class="label">En attente</div>
                <div class="value accent">{{ $invoices->whereIn('status', ['issued', 'partially_paid'])->count() }}</div>
            </div>
            <div class="stat">
                <div class="label">En retard</div>
                <div class="value" style="color:#991b1b">{{ $invoices->where('status', 'overdue')->count() }}</div>
            </div>
        </div>

        <div class="card">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>N°</th>
                            <th>Statut</th>
                            <th>Émise</th>
                            <th>Échéance</th>
                            <th>Total</th>
                            <th>Restant dû</th>
                            <th style="text-align:right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($invoices as $invoice)
                            <tr>
                                <td><strong>{{ $invoice->invoice_number }}</strong></td>
                                <td><span class="badge {{ $invoice->status }}">{{ $invoice->status }}</span></td>
                                <td>{{ $invoice->issue_date?->format('d/m/Y') ?? '—' }}</td>
                                <td>{{ $invoice->due_date?->format('d/m/Y') ?? '—' }}</td>
                                <td>{{ number_format($invoice->total, 0, ',', ' ') }} {{ $invoice->currency }}</td>
                                <td class="amount">{{ number_format($invoice->amount_due, 0, ',', ' ') }} {{ $invoice->currency }}</td>
                                <td style="text-align:right;white-space:nowrap">
                                    <a class="btn outline sm" href="{{ route('client.invoices.show', $invoice) }}">Détails</a>
                                    @if ($invoice->status !== 'draft')
                                        <a class="btn sm" href="{{ route('client.invoices.pdf', $invoice) }}">PDF</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection