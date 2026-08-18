<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Rôles définis par le cahier des charges.
     */
    public const ROLES = [
        'super_admin',
        'admin',
        'finance',
        'commercial',
        'support',
        'network_admin',
        'operator',
        'client',
    ];

    /**
     * Permissions de base par module.
     *
     * @var array<string, list<string>>
     */
    public const PERMISSION_GROUPS = [
        'customer' => ['customer.view', 'customer.create', 'customer.update', 'customer.delete'],
        'subscription' => ['subscription.view', 'subscription.create', 'subscription.update', 'subscription.suspend', 'subscription.terminate', 'subscription.reactivate'],
        'invoice' => ['invoice.view', 'invoice.create', 'invoice.update', 'invoice.cancel', 'invoice.refund'],
        'payment' => ['payment.view', 'payment.create', 'payment.refund'],
        'network' => ['network.view', 'network.sync', 'network.manage'],
        'user' => ['user.view', 'user.create', 'user.update', 'user.delete'],
        'audit' => ['audit.view'],
        'setting' => ['setting.view', 'setting.update'],
    ];

    public function run(): void
    {
        $guard = config('auth.defaults.guard', 'web');

        foreach (self::PERMISSION_GROUPS as $permissions) {
            foreach ($permissions as $permission) {
                Permission::findOrCreate($permission, $guard);
            }
        }

        foreach (self::ROLES as $role) {
            Role::findOrCreate($role, $guard);
        }

        $admin = Role::findByName('admin', $guard);
        $admin->syncPermissions(Permission::all());

        $superAdmin = Role::findByName('super_admin', $guard);
        $superAdmin->syncPermissions(Permission::all());

        Role::findByName('finance', $guard)->syncPermissions([
            'invoice.view', 'invoice.create', 'invoice.update', 'invoice.cancel', 'invoice.refund',
            'payment.view', 'payment.create', 'payment.refund', 'customer.view', 'audit.view',
        ]);

        Role::findByName('commercial', $guard)->syncPermissions([
            'customer.view', 'customer.create', 'customer.update',
            'subscription.view', 'subscription.create', 'subscription.update',
            'invoice.view', 'audit.view',
        ]);

        Role::findByName('support', $guard)->syncPermissions([
            'customer.view', 'customer.update',
            'subscription.view', 'subscription.update', 'subscription.suspend', 'subscription.reactivate',
            'invoice.view', 'payment.view', 'network.view', 'audit.view',
        ]);

        Role::findByName('network_admin', $guard)->syncPermissions([
            'network.view', 'network.sync', 'network.manage',
            'customer.view', 'subscription.view', 'audit.view',
        ]);

        Role::findByName('operator', $guard)->syncPermissions([
            'customer.view', 'subscription.view', 'invoice.view', 'payment.view', 'network.view',
        ]);

        Role::findByName('client', $guard)->syncPermissions([]);
    }
}
