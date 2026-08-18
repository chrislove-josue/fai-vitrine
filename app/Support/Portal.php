<?php

namespace App\Support;

/**
 * Règles pures de mapping rôle → portail (testables sans base de données).
 */
class Portal
{
    public const CLIENT_ROLE = 'client';

    /**
     * Nom de route du portail cible pour un rôle donné.
     */
    public static function routeForRole(string $role): string
    {
        return $role === self::CLIENT_ROLE ? 'dashboard.index' : 'admin.index';
    }

    /**
     * Un rôle « staff » est tout rôle non-client.
     */
    public static function isStaffRole(string $role): bool
    {
        return $role !== self::CLIENT_ROLE;
    }
}
