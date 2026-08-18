@extends('layouts.app')

@section('title', 'Ma consommation')

@section('content')
    <div class="page-head">
        <div>
            <p class="eyebrow">Espace client &middot; JENY SAS</p>
            <h1>Ma consommation</h1>
            <p class="page-sub">Volume de données échangé et sessions réseau de votre connexion.</p>
        </div>
    </div>

    @if ($customer === null)
        <div class="card">
            <p class="muted">Aucun compte client n'est rattaché à cet utilisateur.</p>
        </div>
    @else
        <div class="grid">
            <div class="stat">
                <div class="label">Reçu (download)</div>
                <div class="value green">{{ number_format($summary['bytes_in'] / 1048576, 1, ',', ' ') }} Mo</div>
                <div class="sub">{{ number_format($summary['bytes_in'] / 1073741824, 2, ',', ' ') }} Go</div>
            </div>
            <div class="stat">
                <div class="label">Envoyé (upload)</div>
                <div class="value">{{ number_format($summary['bytes_out'] / 1048576, 1, ',', ' ') }} Mo</div>
                <div class="sub">{{ number_format($summary['bytes_out'] / 1073741824, 2, ',', ' ') }} Go</div>
            </div>
            <div class="stat">
                <div class="label">Total</div>
                <div class="value accent">{{ number_format(($summary['bytes_in'] + $summary['bytes_out']) / 1048576, 0, ',', ' ') }} Mo</div>
                <div class="sub">données échangées</div>
            </div>
            <div class="stat">
                <div class="label">Sessions</div>
                <div class="value">{{ $summary['total'] }}</div>
                <div class="sub">{{ $summary['active'] }} actives actuellement</div>
            </div>
        </div>

        <div class="card">
            <div class="card-head">
                <h2><svg class="ic" width="16" height="16" viewBox="0 0 20 20" fill="currentColor"><path d="M2 4a2 2 0 012-2h12a2 2 0 012 2v12a2 2 0 01-2 2H4a2 2 0 01-2-2V4z"/></svg>
                    Sessions réseau</h2>
            </div>
            @if ($sessions->isEmpty())
                <p class="muted" style="margin:0">Aucune session enregistrée.</p>
            @else
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Début</th>
                                <th>Fin</th>
                                <th>Adresse IP</th>
                                <th>Reçu</th>
                                <th>Envoyé</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($sessions as $session)
                                <tr>
                                    <td>{{ $session->started_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                    <td>
                                        @if ($session->ended_at)
                                            {{ $session->ended_at->format('d/m/Y H:i') }}
                                        @else
                                            <span class="badge active">en cours</span>
                                        @endif
                                    </td>
                                    <td>{{ $session->ip_address ?? '—' }}</td>
                                    <td>{{ number_format(($session->bytes_in ?? 0) / 1048576, 1, ',', ' ') }} Mo</td>
                                    <td>{{ number_format(($session->bytes_out ?? 0) / 1048576, 1, ',', ' ') }} Mo</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @endif
@endsection