@extends('layouts.app')

@section('title', 'Mon profil')

@section('content')
    <div class="page-head">
        <div>
            <p class="eyebrow">Espace client &middot; JENY SAS</p>
            <h1>Mon profil</h1>
            <p class="page-sub">Vos coordonnées, contacts, adresses et documents enregistrés.</p>
        </div>
    </div>

    {{-- En-tête profil --}}
    <div class="card" style="padding:1.75rem 2rem;margin-bottom:1.25rem;">
        <div style="display:flex;align-items:center;gap:1.5rem;flex-wrap:wrap;">
            <div style="width:80px;height:80px;border-radius:50%;background:var(--jeny-primary);color:var(--jeny-accent);display:flex;align-items:center;justify-content:center;font-size:2rem;font-weight:700;flex-shrink:0;">
                {{ strtoupper(mb_substr($customer->display_name ?? 'C', 0, 1)) }}
            </div>
            <div style="flex:1;min-width:0;">
                <h2 style="margin:0;font-size:1.15rem;font-weight:700;color:var(--text-primary);display:flex;align-items:center;gap:.5rem;">
                    {{ $customer->display_name }}
                    <span class="badge {{ $customer->status ?? 'active' }}">{{ ucfirst($customer->status ?? 'actif') }}</span>
                </h2>
                <p style="margin:.3rem 0 0;font-size:.82rem;color:var(--text-secondary);">
                    <i class="bi bi-hash"></i> {{ $customer->customer_number }}
                    &middot;
                    <i class="bi bi-building"></i> {{ $customer->type === 'company' ? 'Entreprise' : 'Particulier' }}
                </p>
            </div>
            <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
                @if($customer->email)
                    <a href="mailto:{{ $customer->email }}" class="btn outline sm">
                        <i class="bi bi-envelope"></i> Email
                    </a>
                @endif
                @if($customer->phone)
                    <a href="tel:{{ $customer->phone }}" class="btn outline sm">
                        <i class="bi bi-telephone"></i> Appeler
                    </a>
                @endif
            </div>
        </div>
    </div>

    {{-- Infos personnelles --}}
    <div class="card">
        <h2><i class="bi bi-person" style="color:var(--jeny-primary);"></i> Informations personnelles</h2>
        <dl class="dl">
            <dt>Nom complet</dt>
            <dd>{{ $customer->display_name }}</dd>
            <dt>N° client</dt>
            <dd style="font-family:monospace;letter-spacing:.04em;">{{ $customer->customer_number }}</dd>
            <dt>Type de compte</dt>
            <dd>
                <span class="badge {{ $customer->type === 'company' ? 'active' : 'cancelled' }}">
                    <i class="bi {{ $customer->type === 'company' ? 'bi-building' : 'bi-person' }}"></i>
                    {{ $customer->type === 'company' ? 'Entreprise' : 'Particulier' }}
                </span>
            </dd>
            <dt>Email</dt>
            <dd>{{ $customer->email ?? '—' }}</dd>
            <dt>Téléphone</dt>
            <dd>{{ $customer->phone ?? '—' }}</dd>
            <dt>Date de naissance</dt>
            <dd>{{ $customer->birth_date?->format('d/m/Y') ?? '—' }}</dd>
        </dl>
    </div>

    {{-- Contacts --}}
    <div class="card">
        <div class="card-head">
            <h2><i class="bi bi-chat-dots" style="color:var(--jeny-primary);"></i> Contacts</h2>
            @if($customer->contacts->isNotEmpty())
                <span class="badge cancelled">{{ $customer->contacts->count() }} enregistré(s)</span>
            @endif
        </div>
        @if ($customer->contacts->isEmpty())
            <div style="text-align:center;padding:2rem 1rem;color:var(--text-secondary);">
                <i class="bi bi-chat-dots" style="font-size:2rem;opacity:.3;display:block;margin-bottom:.5rem;"></i>
                Aucun contact enregistré.
            </div>
        @else
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th><i class="bi bi-tag"></i> Type</th>
                            <th><i class="bi bi-pencil"></i> Valeur</th>
                            <th><i class="bi bi-star"></i> Principal</th>
                            <th><i class="bi bi-shield-check"></i> Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($customer->contacts as $contact)
                            <tr>
                                <td>
                                    <span style="display:inline-flex;align-items:center;gap:.35rem;">
                                        <i class="bi bi-{{ $contact->type === 'email' ? 'envelope' : ($contact->type === 'phone' ? 'telephone' : 'person') }}" style="color:var(--jeny-primary);"></i>
                                        <strong>{{ ucfirst($contact->type) }}</strong>
                                    </span>
                                </td>
                                <td>{{ $contact->value }}</td>
                                <td>
                                    @if($contact->is_primary)
                                        <span class="badge grace_period"><i class="bi bi-star-fill"></i> Principal</span>
                                    @else
                                        <span class="muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($contact->is_verified)
                                        <span class="badge verified">Vérifié</span>
                                    @else
                                        <span class="badge draft">Non vérifié</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Adresses --}}
    <div class="card">
        <div class="card-head">
            <h2><i class="bi bi-geo-alt" style="color:var(--jeny-primary);"></i> Adresses</h2>
            @if($customer->addresses->isNotEmpty())
                <span class="badge cancelled">{{ $customer->addresses->count() }} enregistrée(s)</span>
            @endif
        </div>
        @if ($customer->addresses->isEmpty())
            <div style="text-align:center;padding:2rem 1rem;color:var(--text-secondary);">
                <i class="bi bi-geo-alt" style="font-size:2rem;opacity:.3;display:block;margin-bottom:.5rem;"></i>
                Aucune adresse enregistrée.
            </div>
        @else
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th><i class="bi bi-tag"></i> Type</th>
                            <th><i class="bi bi-house"></i> Adresse</th>
                            <th><i class="bi bi-building"></i> Ville</th>
                            <th><i class="bi bi-globe"></i> Pays</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($customer->addresses as $address)
                            <tr>
                                <td><strong>{{ ucfirst($address->type) }}</strong></td>
                                <td>{{ trim($address->address_line_1.' '.$address->address_line_2) }}</td>
                                <td>{{ $address->city ?? '—' }}</td>
                                <td>{{ $address->country ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Documents --}}
    <div class="card">
        <div class="card-head">
            <h2><i class="bi bi-file-earmark-text" style="color:var(--jeny-primary);"></i> Documents</h2>
            @if($customer->documents->isNotEmpty())
                <span class="badge cancelled">{{ $customer->documents->count() }} document(s)</span>
            @endif
        </div>
        @if ($customer->documents->isEmpty())
            <div style="text-align:center;padding:2rem 1rem;color:var(--text-secondary);">
                <i class="bi bi-file-earmark-text" style="font-size:2rem;opacity:.3;display:block;margin-bottom:.5rem;"></i>
                Aucun document.
            </div>
        @else
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th><i class="bi bi-tag"></i> Type</th>
                            <th><i class="bi bi-file-earmark"></i> Fichier</th>
                            <th><i class="bi bi-info-circle"></i> Statut</th>
                            <th><i class="bi bi-calendar-check"></i> Vérifié le</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($customer->documents as $document)
                            <tr>
                                <td>
                                    <span style="display:inline-flex;align-items:center;gap:.35rem;">
                                        <i class="bi bi-file-earmark" style="color:var(--jeny-primary);"></i>
                                        <strong>{{ ucfirst($document->type) }}</strong>
                                    </span>
                                </td>
                                <td>{{ $document->file_name }}</td>
                                <td><span class="badge {{ $document->status }}">{{ ucfirst($document->status) }}</span></td>
                                <td>{{ $document->verified_at?->format('d/m/Y') ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
