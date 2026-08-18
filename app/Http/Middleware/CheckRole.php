<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Contrôle d'accès par rôle.
 *
 * Usage : `role:client`, `role:staff`, `role:admin,finance`.
 * La valeur spéciale `staff` désigne tout rôle non-client.
 */
class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->route('login');
        }

        foreach ($roles as $role) {
            if ($role === 'staff' && $user->isStaff()) {
                return $next($request);
            }

            if ($role !== 'staff' && $user->hasRole($role)) {
                return $next($request);
            }
        }

        abort(403);
    }
}
