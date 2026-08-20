@extends('layouts.app')

@section('title', 'Tableau de bord')

@section('content')
    @if ($customer === null)
        <div class="card" style="text-align:center;padding:3rem 2rem;">
            <i class="bi bi-person-x" style="font-size:3rem;color:var(--text-secondary);opacity:.3;display:block;margin-bottom:.75rem;"></i>
            <p class="muted" style="font-size:.9rem;">Aucun compte client n'est rattaché à cet utilisateur.</p>
        </div>
    @else
        {{-- HERO --}}
        <section class="hero">
            <div style="display:flex;align-items:center;gap:1rem;">
                <div style="width:56px;height:56px;border-radius:50%;background:rgba(201,162,75,.2);color:var(--jeny-accent);display:flex;align-items:center;justify-content:center;font-size:1.5rem;font-weight:700;flex-shrink:0;border:2px solid rgba(201,162,75,.4);">
                    {{ strtoupper(mb_substr($customer->display_name ?? 'C', 0, 1)) }}
                </div>
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
        <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
            <div class="stat accent-bar green" style="position:relative">
                <div style="display:flex;align-items:center;justify-content:space-between;">
                    <div class="label">Abonnements</div>
                    <i class="bi bi-wifi" style="color:var(--success);font-size:1.1rem;"></i>
                </div>
                <div class="value">{{ $subscriptions->count() }}</div>
                <div class="sub">{{ $subscriptions->where('status', 'active')->count() }} actif(s)</div>
            </div>
            <div class="stat accent-bar orange" style="position:relative">
                <div style="display:flex;align-items:center;justify-content:space-between;">
                    <div class="label">Factures à régler</div>
                    <i class="bi bi-receipt" style="color:var(--jeny-accent);font-size:1.1rem;"></i>
                </div>
                <div class="value" style="color:var(--jeny-accent)">{{ $upcomingInvoices->count() }}</div>
                <div class="sub">montant total : {{ number_format($upcomingInvoices->sum('amount_due'), 0, ',', ' ') }} XOF</div>
            </div>
            <div class="stat accent-bar" style="position:relative">
                <div style="display:flex;align-items:center;justify-content:space-between;">
                    <div class="label">Sessions actives</div>
                    <i class="bi bi-graph-up" style="color:var(--jeny-primary);font-size:1.1rem;"></i>
                </div>
                <div class="value">{{ $sessionSummary['active'] }}</div>
                <div class="sub">{{ number_format(($sessionSummary['bytes_in'] + $sessionSummary['bytes_out']) / 1048576, 0, ',', ' ') }} Mo consommés</div>
            </div>
            <div class="stat accent-bar green" style="position:relative">
                <div style="display:flex;align-items:center;justify-content:space-between;">
                    <div class="label">Dernier paiement</div>
                    <i class="bi bi-credit-card" style="color:var(--success);font-size:1.1rem;"></i>
                </div>
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
                $clientAlerts[] = ['type' => 'danger', 'icon' => 'bi-exclamation-octagon', 'title' => $overdueInvoices->count() . ' facture(s) en retard', 'text' => 'Veuillez régler vos factures impayées au plus vite.'];
            }
            $graceSubscriptions = $subscriptions->where('status', 'grace_period');
            if ($graceSubscriptions->isNotEmpty()) {
                $clientAlerts[] = ['type' => 'warning', 'icon' => 'bi-exclamation-triangle', 'title' => 'Période de grâce active', 'text' => 'Votre abonnement sera suspendu si le paiement n\'est pas reçu.'];
            }
            foreach ($subscriptions->where('status', 'active') as $sub) {
                if ($sub->expires_at && $sub->expires_at->diffInDays(now()) <= 7 && $sub->expires_at->isFuture()) {
                    $clientAlerts[] = ['type' => 'warning', 'icon' => 'bi-clock-history', 'title' => 'Abonnement expire le ' . $sub->expires_at->format('d/m/Y'), 'text' => 'Renouvelez avant expiration pour éviter la coupure.'];
                }
            }
        @endphp

        @if (count($clientAlerts) > 0)
            <div class="card" style="padding:1rem 1.25rem">
                <h2 style="margin-bottom:.75rem"><i class="bi bi-bell" style="color:var(--jeny-primary);"></i> Alertes</h2>
                @foreach ($clientAlerts as $alert)
                    <div class="alert-card {{ $alert['type'] }}">
                        <span class="alert-icon">
                            <i class="bi {{ $alert['icon'] }}"></i>
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
            <h2 style="margin-bottom:.75rem"><i class="bi bi-lightning" style="color:var(--jeny-primary);"></i> Actions rapides</h2>
            <div class="quick-actions">
                @if ($upcomingInvoices->isNotEmpty())
                    <a class="quick-action accent" href="{{ route('client.invoices.index') }}">
                        <i class="bi bi-wallet2"></i>
                        Régler mes factures ({{ $upcomingInvoices->count() }})
                    </a>
                @endif
                <a class="quick-action primary" href="{{ route('client.sessions.index') }}">
                    <i class="bi bi-graph-up"></i>
                    Voir ma consommation
                </a>
                <a class="quick-action outline" href="{{ route('client.profile.show') }}">
                    <i class="bi bi-person"></i>
                    Mon profil
                </a>
            </div>
        </div>

        {{-- GRAPHIQUE CONSOMMATION --}}
        @if ($sessionSummary['bytes_in'] + $sessionSummary['bytes_out'] > 0)
            <div class="card">
                <div class="card-head">
                    <h2><i class="bi bi-bar-chart-line" style="color:var(--jeny-primary);"></i> Consommation réseau</h2>
                </div>
                @php
                    $totalBytes = $sessionSummary['bytes_in'] + $sessionSummary['bytes_out'];
                    $downloadPct = $totalBytes > 0 ? round(($sessionSummary['bytes_in'] / $totalBytes) * 100) : 50;
                    $uploadPct = 100 - $downloadPct;
                @endphp
                <div style="margin-bottom:.75rem">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.35rem">
                        <span style="font-size:.78rem;font-weight:600;color:var(--jeny-primary);display:flex;align-items:center;gap:.35rem;">
                            <i class="bi bi-arrow-down-circle"></i> Téléchargement
                        </span>
                        <span style="font-size:.78rem;font-weight:600;color:var(--text-secondary)">{{ number_format($sessionSummary['bytes_in'] / 1048576, 1, ',', ' ') }} Mo</span>
                    </div>
                    <div style="height:8px;background:var(--bg-secondary);border-radius:9999px;overflow:hidden">
                        <div style="height:100%;width:{{ $downloadPct }}%;background:var(--jeny-primary);border-radius:9999px;transition:width .3s"></div>
                    </div>
                </div>
                <div>
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.35rem">
                        <span style="font-size:.78rem;font-weight:600;color:var(--jeny-accent);display:flex;align-items:center;gap:.35rem;">
                            <i class="bi bi-arrow-up-circle"></i> Envoi
                        </span>
                        <span style="font-size:.78rem;font-weight:600;color:var(--text-secondary)">{{ number_format($sessionSummary['bytes_out'] / 1048576, 1, ',', ' ') }} Mo</span>
                    </div>
                    <div style="height:8px;background:var(--bg-secondary);border-radius:9999px;overflow:hidden">
                        <div style="height:100%;width:{{ $uploadPct }}%;background:var(--jeny-accent);border-radius:9999px;transition:width .3s"></div>
                    </div>
                </div>
                <div style="margin-top:.75rem;display:flex;gap:1.5rem;flex-wrap:wrap;">
                    <div style="font-size:.75rem;color:var(--text-secondary);display:flex;align-items:center;gap:.3rem;">
                        <i class="bi bi-database"></i>
                        <strong style="color:var(--text-primary)">{{ number_format($totalBytes / 1048576, 0, ',', ' ') }} Mo</strong> total échangé
                    </div>
                    <div style="font-size:.75rem;color:var(--text-secondary);display:flex;align-items:center;gap:.3rem;">
                        <i class="bi bi-activity"></i>
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
                        <div style="display:flex;align-items:center;justify-content:space-between;">
                            <div class="label">Prochaine échéance</div>
                            <i class="bi bi-calendar-event" style="font-size:.9rem;color:var(--text-secondary);"></i>
                        </div>
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
                    <h2><i class="bi bi-wifi" style="color:var(--jeny-primary);"></i> Mes abonnements</h2>
                    <a class="btn outline sm" href="{{ route('client.invoices.index') }}">Voir mes factures</a>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th><i class="bi bi-hash"></i> N°</th>
                                <th><i class="bi bi-tag"></i> Offre</th>
                                <th><i class="bi bi-info-circle"></i> Statut</th>
                                <th><i class="bi bi-calendar-check"></i> Début</th>
                                <th><i class="bi bi-calendar-x"></i> Échéance</th>
                                <th><i class="bi bi-arrow-repeat"></i> Renouvellement</th>
                                <th><i class="bi bi-cash"></i> Prix</th>
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
                                    <td>
                                        <span style="display:inline-flex;align-items:center;gap:.3rem;">
                                            <i class="bi {{ $subscription->auto_renew ? 'bi-check-circle-fill' : 'bi-x-circle-fill' }}" style="font-size:.7rem;color:{{ $subscription->auto_renew ? 'var(--success)' : 'var(--text-secondary)' }};"></i>
                                            {{ $subscription->auto_renew ? 'Automatique' : 'Manuel' }}
                                        </span>
                                    </td>
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
                <h2><i class="bi bi-exclamation-circle" style="color:var(--jeny-primary);"></i> Factures à régler</h2>
            </div>
            @if ($upcomingInvoices->isEmpty())
                <div style="text-align:center;padding:2rem 1rem;color:var(--text-secondary);">
                    <i class="bi bi-check-circle" style="font-size:2rem;color:var(--success);display:block;margin-bottom:.5rem;"></i>
                    Aucune facture en attente. Vous êtes à jour.
                </div>
            @else
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th><i class="bi bi-hash"></i> N°</th>
                                <th><i class="bi bi-info-circle"></i> Statut</th>
                                <th><i class="bi bi-calendar"></i> Échéance</th>
                                <th><i class="bi bi-cash"></i> Restant dû</th>
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
                                        <a class="btn outline sm" href="{{ route('client.invoices.show', $invoice) }}">
                                            <i class="bi bi-eye"></i> Détails
                                        </a>
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
                <h2><i class="bi bi-file-earmark-text" style="color:var(--jeny-primary);"></i> Dernières factures</h2>
                <a class="btn outline sm" href="{{ route('client.invoices.index') }}">Tout voir</a>
            </div>
            @if ($invoices->isEmpty())
                <div style="text-align:center;padding:2rem 1rem;color:var(--text-secondary);">
                    <i class="bi bi-file-earmark-x" style="font-size:2rem;opacity:.3;display:block;margin-bottom:.5rem;"></i>
                    Aucune facture pour le moment.
                </div>
            @else
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th><i class="bi bi-hash"></i> N°</th>
                                <th><i class="bi bi-info-circle"></i> Statut</th>
                                <th><i class="bi bi-calendar-check"></i> Émise</th>
                                <th><i class="bi bi-cash"></i> Total</th>
                                <th><i class="bi bi-wallet2"></i> Restant dû</th>
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
