<?php

namespace App\Filament\Resources\Concerns;

use Illuminate\Database\Eloquent\Model;

/**
 * Contrôle d'accès des ressources Filament par permission Spatie.
 *
 * Chaque ressource déclare son préfixe (ex. 'customer', 'invoice') ; les
 * permissions réelles ('customer.view', 'invoice.create', ...) sont définies
 * par RoleAndPermissionSeeder et attribuées aux rôles staff.
 */
trait AccessControlledResource
{
    /**
     * Préfixe de permission, surchargé par chaque ressource (ex. 'customer').
     */
    protected static function permissionPrefix(): string
    {
        return '';
    }

    protected static function resourcePermission(string $name): string
    {
        return static::permissionPrefix().'.'.$name;
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user !== null && $user->can(static::resourcePermission('view'));
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();

        return $user !== null && $user->can(static::resourcePermission('create'));
    }

    public static function canEdit(Model $record): bool
    {
        $user = auth()->user();

        return $user !== null && $user->can(static::resourcePermission('update'));
    }

    public static function canDelete(Model $record): bool
    {
        $user = auth()->user();

        return $user !== null && $user->can(static::resourcePermission('delete'));
    }
}
