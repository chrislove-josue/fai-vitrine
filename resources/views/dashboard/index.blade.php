@extends('layouts.app')

@section('title', 'Tableau de bord')

@section('content')
    @if ($customer === null)
        <div class="card">
            <p class="muted">Aucun compte client n'est rattaché à cet utilisateur.</p>
        </div>
    @else
        {{-- HERO --}}
        <section class="hero">
            <div>
                <p class="eyebrow" style="color:#C9A24B">Espace client &middot; JENY SAS</p>
                <h1>Bonjour, <span style="color:#C9A24B">{{ $customer->display_name }}</span></h1>
                <p class="hero-sub">
                    N° client <strong>{{ $customer->customer_number }}</strong> &middot;
                    @if ($subscriptions->where('status', 'active')->isNotEmpty())
                        Votre abonnement est <strong style="color:#0F8B5E">actif</strong>
                    @elseif ($subscriptions->where('status', 'grace_period')->isNotEmpty())
                        Votre abonnement est en <strong style="color:#C9A24B">période de grâce</strong>
                    @else
                        Vous n'avez pas d'abonnement actif en ce moment
                    @endif
                </p>
            </div>
            <div class="hero-metrics">
                <div class="hero-metric">
                    <div class="hnum">{{ $subscriptions->count() }}</div>
                    <div class="hlab">Abonnements</div>
                </div>
                <div class="hero-metric">
                    <div class="hnum">{{ $upcomingInvoices->count() }}</div>
                    <div class="hlab">Factures à régler</div>
                </div>
                <div class="hero-metric">
                    <div class="hnum">{{ $sessionSummary['active'] }}</div>
                    <div class="hlab">Sessions actives</div>
                </div>
                <div class="hero-metric">
                    <div class="hnum">{{ now()->locale('fr')->translatedFormat('d/m/Y') }}</div>
                    <div class="hlab">Aujourd'hui</div>
                </div>
            </div>
        </section>

        {{-- KPI CARDS --}}
        <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
            <div class="stat accent-bar green" style="position:relative">
                <div class="label">Abonnements</div>
                <div class="value">{{ $subscriptions->count() }}</div>
                <div class="sub">{{ $subscriptions->where('status', 'active')->count() }} actif(s)</div>
            </div>
            <div class="stat accent-bar" style="position:relative">
                <div class="label">Factures à régler</div>
                <div class="value" style="color:var(--jeny-accent)">{{ $upcomingInvoices->count() }}</div>
                <div class="sub">montant total : {{ number_format($upcomingInvoices->sum('amount_due'), 0, ',', ' ') }} XOF</div>
            </div>
            <div class="stat accent-bar" style="position:relative">
                <div class="label">Sessions actives</div>
                <div class="value">{{ $sessionSummary['active'] }}</div>
                <div class="sub">{{ number_format(($sessionSummary['bytes_in'] + $sessionSummary['bytes_out']) / 1048576, 0, ',', ' ') }} Mo consommés</div>
            </div>
            <div class="stat accent-bar green" style="position:relative">
                <div class="label">Dernier paiement</div>
                <div class="value" style="font-size:1.1rem">
                    @if ($lastPayment)
                        {{ number_format($lastPayment->amount, 0, ',', ' ') }} {{ $lastPayment->currency }}
                    @else
                        <span class="muted" style="font-size:.9rem">Aucun</span>
                    @endif
                </div>
                @if ($lastPayment)
                    <div class="sub">{{ $lastPayment->paid_at?->format('d/m/Y') }} &middot; {{ $lastPayment->payment_reference }}</div>
                @endif
            </div>
        </div>

        {{-- ALERTES --}}
        @php
            $clientAlerts = [];
            $overdueInvoices = $invoices->where('status', 'overdue');
            if ($overdueInvoices->isNotEmpty()) {
                $clientAlerts[] = ['type' => 'danger', 'title' => $overdueInvoices->count() . ' facture(s) en retard', 'text' => 'Veuillez régler vos factures impayées au plus vite.'];
            }
            $graceSubscriptions = $subscriptions->where('status', 'grace_period');
            if ($graceSubscriptions->isNotEmpty()) {
                $clientAlerts[] = ['type' => 'warning', 'title' => 'Période de grâce active', 'text' => 'Votre abonnement sera suspendu si le paiement n\'est pas reçu.'];
            }
            foreach ($subscriptions->where('status', 'active') as $sub) {
                if ($sub->expires_at && $sub->expires_at->diffInDays(now()) <= 7 && $sub->expires_at->isFuture()) {
                    $clientAlerts[] = ['type' => 'warning', 'title' => 'Abonnement expire le ' . $sub->expires_at->format('d/m/Y'), 'text' => 'Renouvelez avant expiration pour éviter la coupure.'];
                }
            }
        @endphp

        @if (count($clientAlerts) > 0)
            <div class="card" style="padding:1rem 1.25rem">
                <h2 style="margin-bottom:.75rem"><svg class="ic" width="16" height="16" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg> Alertes</h2>
                @foreach ($clientAlerts as $alert)
                    <div class="alert-card {{ $alert['type'] }}">
                        <span class="alert-icon">
                            @if ($alert['type'] === 'danger')
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                            @else
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                            @endif
                        </span>
                        <div class="alert-body">
                            <div class="alert-title">{{ $alert['title'] }}</div>
                            <div class="alert-text">{{ $alert['text'] }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- ACTIONS RAPIDES --}}
        <div class="card" style="padding:1rem 1.25rem">
            <h2 style="margin-bottom:.75rem"><svg class="ic" width="16" height="16" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"/></svg> Actions rapides</h2>
            <div class="quick-actions">
                @if ($upcomingInvoices->isNotEmpty())
                    <a class="quick-action accent" href="{{ route('client.invoices.index') }}">
                        <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor"><path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4zm14 5H2v6a2 2 0 002 2h12a2 2 0 002-2V9zM5 13a1 1 0 011-1h1a1 1 0 110 2H6a1 1 0 01-1-1z"/></svg>
                        Régler mes factures ({{ $upcomingInvoices->count() }})
                    </a>
                @endif
                <a class="quick-action primary" href="{{ route('client.sessions.index') }}">
                    <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M2 10a8 8 0 1116 0 8 8 0 01-16 0zm11.3-3.3a1 1 0 00-1.4-1.4L9 8.6 7.7 7.3a1 1 0 00-1.4 1.4l2 2a1 1 0 001.4 0l4-4z" clip-rule="evenodd"/></svg>
                    Voir ma consommation
                </a>
                <a class="quick-action outline" href="{{ route('client.profile.show') }}">
                    <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/></svg>
                    Mon profil
                </a>
            </div>
        </div>

        {{-- GRAPHIQUE CONSOMMATION (barre simple) --}}
        @if ($sessionSummary['bytes_in'] + $sessionSummary['bytes_out'] > 0)
            <div class="card">
                <div class="card-head">
                    <h2><svg class="ic" width="16" height="16" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 3a1 1 0 000 2v8a2 2 0 002 2h2.586l-1.293 1.293a1 1 0 101.414 1.414L10 15.414l2.293 2.293a1 1 0 001.414-1.414L12.414 15H15a2 2 0 002-2V5a1 1 0 100-2H3zm11 4a1 1 0 10-2 0v4a1 1 0 102 0V7zm-3 1a1 1 0 10-2 0v3a1 1 0 102 0V8zM8 9a1 1 0 00-2 0v2a1 1 0 102 0V9z" clip-rule="evenodd"/></svg>
                        Consommation réseau</h2>
                </div>
                @php
                    $totalBytes = $sessionSummary['bytes_in'] + $sessionSummary['bytes_out'];
                    $downloadPct = $totalBytes > 0 ? round(($sessionSummary['bytes_in'] / $totalBytes) * 100) : 50;
                    $uploadPct = 100 - $downloadPct;
                @endphp
                <div style="margin-bottom:.75rem">
                    <div style="display:flex;justify-content:space-between;margin-bottom:.35rem">
                        <span style="font-size:.78rem;font-weight:600;color:var(--jeny-primary)">Téléchargement</span>
                        <span style="font-size:.78rem;font-weight:600;color:var(--text-secondary)">{{ number_format($sessionSummary['bytes_in'] / 1048576, 1, ',', ' ') }} Mo</span>
                    </div>
                    <div style="height:8px;background:var(--bg-secondary);border-radius:9999px;overflow:hidden">
                        <div style="height:100%;width:{{ $downloadPct }}%;background:var(--jeny-primary);border-radius:9999px;transition:width .3s"></div>
                    </div>
                </div>
                <div>
                    <div style="display:flex;justify-content:space-between;margin-bottom:.35rem">
                        <span style="font-size:.78rem;font-weight:600;color:var(--jeny-accent)">Envoi</span>
                        <span style="font-size:.78rem;font-weight:600;color:var(--text-secondary)">{{ number_format($sessionSummary['bytes_out'] / 1048576, 1, ',', ' ') }} Mo</span>
                    </div>
                    <div style="height:8px;background:var(--bg-secondary);border-radius:9999px;overflow:hidden">
                        <div style="height:100%;width:{{ $uploadPct }}%;background:var(--jeny-accent);border-radius:9999px;transition:width .3s"></div>
                    </div>
                </div>
                <div style="margin-top:.75rem;display:flex;gap:1.5rem">
                    <div style="font-size:.75rem;color:var(--text-secondary)">
                        <strong style="color:var(--text-primary)">{{ number_format($totalBytes / 1048576, 0, ',', ' ') }} Mo</strong> total échangé
                    </div>
                    <div style="font-size:.75rem;color:var(--text-secondary)">
                        <strong style="color:var(--text-primary)">{{ $sessionSummary['active'] }}</strong> session(s) active(s)
                    </div>
                </div>
            </div>
        @endif

        {{-- PROCHAINES ÉCHÉANCES --}}
        @if ($subscriptions->whereIn('status', ['active', 'grace_period'])->isNotEmpty())
            <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));">
                @foreach ($subscriptions->whereIn('status', ['active', 'grace_period'])->take(3) as $subscription)
                    <div class="stat accent-bar {{ $subscription->status === 'active' ? 'green' : 'orange' }}" style="position:relative">
                        <div class="label">Prochaine échéance</div>
                        <div class="value" style="font-size:1.1rem">
                            {{ $subscription->expires_at?->format('d/m/Y') ?? '—' }}
                        </div>
                        <div class="sub">
                            <span class="badge {{ $subscription->status }}">{{ $subscription->status }}</span>
                            &nbsp;{{ $subscription->auto_renew ? 'renouvellement auto' : 'sans renouvellement' }}
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- ABONNEMENTS --}}
        @if ($subscriptions->isNotEmpty())
            <div class="card">
                <div class="card-head">
                    <h2><svg class="ic" width="16" height="16" viewBox="0 0 20 20" fill="currentColor"><path d="M10 2a4 4 0 00-4 4v1H5a2 2 0 00-2 2v7a2 2 0 002 2h10a2 2 0 002-2V9a2 2 0 00-2-2h-1V6a4 4 0 00-4-4zm2 5V6a2 2 0 10-4 0v1h4z"/></svg>
                        Mes abonnements</h2>
                    <a class="btn outline sm" href="{{ route('client.invoices.index') }}">Voir mes factures</a>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>N°</th>
                                <th>Offre</th>
                                <th>Statut</th>
                                <th>Début</th>
                                <th>Échéance</th>
                                <th>Renouvellement</th>
                                <th>Prix</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($subscriptions as $subscription)
                                <tr>
                                    <td><strong>{{ $subscription->subscription_number }}</strong></td>
                                    <td>{{ $subscription->offer?->name ?? '—' }}</td>
                                    <td><span class="badge {{ $subscription->status }}">{{ $subscription->status }}</span></td>
                                    <td>{{ $subscription->starts_at?->format('d/m/Y') ?? '—' }}</td>
                                    <td>{{ $subscription->expires_at?->format('d/m/Y') ?? '—' }}</td>
                                    <td>{{ $subscription->auto_renew ? 'Automatique' : 'Manuel' }}</td>
                                    <td class="amount">{{ number_format($subscription->price, 0, ',', ' ') }} {{ $subscription->currency }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- FACTURES À RÉGLER --}}
        <div class="card">
            <div class="card-head">
                <h2><svg class="ic" width="16" height="16" viewBox="0 0 20 20" fill="currentColor"><path d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z"/></svg>
                    Factures à régler</h2>
            </div>
            @if ($upcomingInvoices->isEmpty())
                <p class="muted" style="margin:0">Aucune facture en attente. Vous êtes à jour. ✓</p>
            @else
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>N°</th>
                                <th>Statut</th>
                                <th>Échéance</th>
                                <th>Restant dû</th>
                                <th style="text-align:right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($upcomingInvoices as $invoice)
                                <tr>
                                    <td><strong>{{ $invoice->invoice_number }}</strong></td>
                                    <td><span class="badge {{ $invoice->status }}">{{ $invoice->status }}</span></td>
                                    <td>{{ $invoice->due_date?->format('d/m/Y') ?? '—' }}</td>
                                    <td class="amount" style="color:var(--jeny-accent)">{{ number_format($invoice->amount_due, 0, ',', ' ') }} {{ $invoice->currency }}</td>
                                    <td style="text-align:right;white-space:nowrap">
                                        <a class="btn outline sm" href="{{ route('client.invoices.show', $invoice) }}">Détails</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- DERNIÈRES FACTURES --}}
        <div class="card">
            <div class="card-head">
                <h2><svg class="ic" width="16" height="16" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h12a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm6 2a1 1 0 100 2h4a1 1 0 100-2h-4zm-3 3a1 1 0 011-1h6a1 1 0 110 2H8a1 1 0 01-1-1zm1 3a1 1 0 100 2h4a1 1 0 100-2H8z" clip-rule="evenodd"/></svg>
                    Dernières factures</h2>
                <a class="btn outline sm" href="{{ route('client.invoices.index') }}">Tout voir</a>
            </div>
            @if ($invoices->isEmpty())
                <p class="muted" style="margin:0">Aucune facture pour le moment.</p>
            @else
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>N°</th>
                                <th>Statut</th>
                                <th>Émise</th>
                                <th>Total</th>
                                <th>Restant dû</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($invoices->sortByDesc('issue_date')->take(6) as $invoice)
                                <tr>
                                    <td><strong>{{ $invoice->invoice_number }}</strong></td>
                                    <td><span class="badge {{ $invoice->status }}">{{ $invoice->status }}</span></td>
                                    <td>{{ $invoice->issue_date?->format('d/m/Y') ?? '—' }}</td>
                                    <td>{{ number_format($invoice->total, 0, ',', ' ') }} {{ $invoice->currency }}</td>
                                    <td class="amount">{{ number_format($invoice->amount_due, 0, ',', ' ') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @endif
@endsection
