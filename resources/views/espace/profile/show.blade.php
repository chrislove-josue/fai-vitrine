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

    <div class="card">
        <h2><svg class="ic" width="16" height="16" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/></svg>
            Identité</h2>
        <dl class="dl">
            <dt>Nom</dt>
            <dd>{{ $customer->display_name }}</dd>
            <dt>N° client</dt>
            <dd>{{ $customer->customer_number }}</dd>
            <dt>Type</dt>
            <dd>{{ $customer->type === 'company' ? 'Entreprise' : 'Particulier' }}</dd>
            <dt>Email</dt>
            <dd>{{ $customer->email ?? '—' }}</dd>
            <dt>Téléphone</dt>
            <dd>{{ $customer->phone ?? '—' }}</dd>
            <dt>Date de naissance</dt>
            <dd>{{ $customer->birth_date?->format('d/m/Y') ?? '—' }}</dd>
        </dl>
    </div>

    <div class="card">
        <h2><svg class="ic" width="16" height="16" viewBox="0 0 20 20" fill="currentColor"><path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/><path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/></svg>
            Contacts</h2>
        @if ($customer->contacts->isEmpty())
            <p class="muted" style="margin:0">Aucun contact enregistré.</p>
        @else
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Valeur</th>
                            <th>Principal</th>
                            <th>Vérifié</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($customer->contacts as $contact)
                            <tr>
                                <td><strong>{{ $contact->type }}</strong></td>
                                <td>{{ $contact->value }}</td>
                                <td>{{ $contact->is_primary ? 'Oui' : 'Non' }}</td>
                                <td>
                                    @if ($contact->is_verified)
                                        <span class="badge verified">Vérifié</span>
                                    @else
                                        <span class="muted">Non</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="card">
        <h2><svg class="ic" width="16" height="16" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 2a6 6 0 00-6 6c0 4 3.5 6.5 5.5 9.5.5.8 1.5.8 2 0C12.5 14.5 16 12 16 8a6 6 0 00-6-6zm0 7a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/></svg>
            Adresses</h2>
        @if ($customer->addresses->isEmpty())
            <p class="muted" style="margin:0">Aucune adresse enregistrée.</p>
        @else
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Adresse</th>
                            <th>Ville</th>
                            <th>Pays</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($customer->addresses as $address)
                            <tr>
                                <td><strong>{{ $address->type }}</strong></td>
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

    <div class="card">
        <h2><svg class="ic" width="16" height="16" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/></svg>
            Documents</h2>
        @if ($customer->documents->isEmpty())
            <p class="muted" style="margin:0">Aucun document.</p>
        @else
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Fichier</th>
                            <th>Statut</th>
                            <th>Vérifié</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($customer->documents as $document)
                            <tr>
                                <td><strong>{{ $document->type }}</strong></td>
                                <td>{{ $document->file_name }}</td>
                                <td><span class="badge {{ $document->status }}">{{ $document->status }}</span></td>
                                <td>{{ $document->verified_at?->format('d/m/Y') ?? 'Non' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection