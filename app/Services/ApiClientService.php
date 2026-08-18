<?php

namespace App\Services;

use App\Models\ApiClient;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Gestion du cycle de vie des clients API (connexions système).
 *
 * Chaque client dispose d'un identifiant public et d'un secret privé
 * stocké uniquement sous forme de hash bcrypt. La vérification se fait
 * en temps constant (Hash::check) pour éviter les attaques par timing.
 */
class ApiClientService
{
    public function createClient(string $name, string $type = 'internal', ?string $expiresAt = null): array
    {
        $clientId = 'cli_'.Str::random(24);
        $secret = Str::random(48);

        $client = ApiClient::create([
            'name' => $name,
            'client_id' => $clientId,
            'secret_hash' => bcrypt($secret),
            'type' => $type,
            'status' => 'active',
            'expires_at' => $expiresAt,
        ]);

        return [
            'client' => $client,
            'secret' => $secret,
        ];
    }

    /**
     * Vérifie les identifiants d'un client API.
     *
     * @return ApiClient|null le client si l'authentification réussit
     */
    public function authenticate(string $clientId, string $secret): ?ApiClient
    {
        $client = ApiClient::where('client_id', $clientId)->where('status', 'active')->first();

        if ($client === null) {
            return null;
        }

        if ($client->expires_at !== null && $client->expires_at->isPast()) {
            return null;
        }

        if (! Hash::check($secret, $client->secret_hash)) {
            return null;
        }

        $client->update(['last_used_at' => now()]);

        return $client;
    }

    public function revoke(ApiClient $client): void
    {
        $client->update(['status' => 'revoked']);
    }
}
