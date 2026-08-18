<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bloque les utilisateurs inactifs / bloqués / supprimés.
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && $user->status !== 'active') {
            auth()->logout();

            return redirect()->route('login')->withErrors([
                'email' => 'Votre compte est inactif ou bloqué. Contactez l\'assistance.',
            ]);
        }

        return $next($request);
    }
}
