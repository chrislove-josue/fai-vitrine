<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Facture {{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: Montserrat, DejaVu Sans, sans-serif; font-size: 12px; color: #10151F; margin: 0; }
        .header { width: 100%; }
        .brand { font-size: 18px; font-weight: bold; color: #0B2545; }
        .muted { color: #667085; }
        .right { text-align: right; }
        .meta { margin-top: 1.5rem; }
        .meta td { padding: .15rem .5rem .15rem 0; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 2rem; }
        table.items th, table.items td { border: 1px solid #E4EAF2; padding: .5rem; text-align: left; }
        table.items th { background: #F7F5F0; }
        table.items td.num, table.items th.num { text-align: right; }
        .totals { width: 45%; margin-left: auto; margin-top: 1rem; }
        .totals td { padding: .3rem .5rem; }
        .totals td.num { text-align: right; }
        .grand { font-weight: bold; font-size: 13px; }
        .footer { margin-top: 2.5rem; font-size: 10px; color: #667085; }
        .badge { border: 1px solid #0B2545; color: #0B2545; padding: .1rem .5rem; border-radius: 6px; font-size: 10px; }
    </style>
</head>
<body>
    <table class="header">
        <tr>
            <td>
                <div class="brand">{{ config('app.name') }}</div>
                <div class="muted">FACTURE</div>
            </td>
            <td class="right">
                <div>N° : <strong>{{ $invoice->invoice_number }}</strong></div>
                <div class="muted">Statut : <span class="badge">{{ $invoice->status }}</span></div>
                <div class="muted">Émise le : {{ $invoice->issue_date?->format('d/m/Y') }}</div>
                <div class="muted">Échéance : {{ $invoice->due_date?->format('d/m/Y') }}</div>
            </td>
        </tr>
    </table>

    <table class="meta">
        <tr>
            <td>
                <div class="muted">CLIENT</div>
                <div><strong>{{ $invoice->customer->display_name }}</strong></div>
                <div>{{ $invoice->customer->customer_number }}</div>
                @if ($invoice->customer->email)
                    <div>{{ $invoice->customer->email }}</div>
                @endif
                @if ($invoice->customer->phone)
                    <div>{{ $invoice->customer->phone }}</div>
                @endif
            </td>
            <td class="right">
                @if ($invoice->subscription)
                    <div class="muted">Abonnement : {{ $invoice->subscription->subscription_number }}</div>
                @endif
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th>Description</th>
                <th class="num">Qté</th>
                <th class="num">P.U.</th>
                <th class="num">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->items as $item)
                <tr>
                    <td>{{ $item->description }}</td>
                    <td class="num">{{ $item->quantity }}</td>
                    <td class="num">{{ number_format($item->unit_price, 0, ',', ' ') }}</td>
                    <td class="num">{{ number_format($item->total, 0, ',', ' ') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td>Sous-total</td>
            <td class="num">{{ number_format($invoice->subtotal, 0, ',', ' ') }} {{ $invoice->currency }}</td>
        </tr>
        @if ((float) $invoice->discount > 0)
            <tr>
                <td>Remise</td>
                <td class="num">-{{ number_format($invoice->discount, 0, ',', ' ') }} {{ $invoice->currency }}</td>
            </tr>
        @endif
        @if ((float) $invoice->tax > 0)
            <tr>
                <td>Taxes</td>
                <td class="num">{{ number_format($invoice->tax, 0, ',', ' ') }} {{ $invoice->currency }}</td>
            </tr>
        @endif
        <tr>
            <td>Total</td>
            <td class="num">{{ number_format($invoice->total, 0, ',', ' ') }} {{ $invoice->currency }}</td>
        </tr>
        <tr>
            <td>Déjà payé</td>
            <td class="num">{{ number_format($invoice->amount_paid, 0, ',', ' ') }} {{ $invoice->currency }}</td>
        </tr>
        <tr class="grand">
            <td>Restant dû</td>
            <td class="num">{{ number_format($invoice->amount_due, 0, ',', ' ') }} {{ $invoice->currency }}</td>
        </tr>
    </table>

    <div class="footer">
        Document généré le {{ now()->format('d/m/Y H:i') }} — {{ config('app.name') }}.
    </div>
</body>
</html>