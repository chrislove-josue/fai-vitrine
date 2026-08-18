<?php

namespace App\Http\Middleware;

use App\Services\ApiClientService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authentification des clients API via en-têtes X-Client-Id / X-Client-Secret.
 *
 * Les routes API métier (isp_core) sont protégées : un appel sans
 * identifiants valides est rejeté avec 401, avant toute lecture de données.
 */
class AuthenticateApiClient
{
    public function __construct(private readonly ApiClientService $clients) {}

    public function handle(Request $request, Closure $next): Response
    {
        $clientId = $request->header('X-Client-Id');
        $secret = $request->header('X-Client-Secret');

        if ($clientId === null || $secret === null) {
            return response()->json(['error' => 'Identifiants API manquants.'], 401);
        }

        $client = $this->clients->authenticate($clientId, $secret);

        if ($client === null) {
            return response()->json(['error' => 'Identifiants API invalides.'], 401);
        }

        $request->attributes->set('api_client', $client);

        return $next($request);
    }
}
