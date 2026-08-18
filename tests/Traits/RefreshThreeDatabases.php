<?php

namespace Tests\Traits;

use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Support\Facades\DB;

/**
 * Migration et isolation transactionnelle des trois bases de données.
 *
 * - isp_application (database/migrations/application)
 * - isp_core        (database/migrations/core)
 * - freeradius      (database/migrations/radius)
 *
 * Les migrations sont exécutées une seule fois par processus de test,
 * puis chaque test est isolé dans une transaction par connexion.
 */
trait RefreshThreeDatabases
{
    /**
     * Connexions migrées par le processus de test courant.
     *
     * @var array<int, string>
     */
    protected static array $threeDatabasesMigrated = [];

    /**
     * Rôles et permissions ensemencés une seule fois par processus.
     */
    protected static bool $threeDatabasesRolesSeeded = false;

    /**
     * Connexions englobées dans une transaction par test.
     *
     * @var array<int, string>
     */
    protected array $connectionsToTransact = ['isp_application', 'isp_core', 'freeradius'];

    protected function refreshDatabasesAndBeginTransactions(): void
    {
        $this->migrateThreeDatabases();

        $this->seedRolesOnce();

        foreach ($this->connectionsToTransact as $connection) {
            if (DB::connection($connection)->transactionLevel() === 0) {
                DB::connection($connection)->beginTransaction();
            }
        }
    }

    protected function seedRolesOnce(): void
    {
        if (static::$threeDatabasesRolesSeeded) {
            return;
        }

        $this->seed(RoleAndPermissionSeeder::class);

        static::$threeDatabasesRolesSeeded = true;
    }

    protected function rollbackAllTransactions(): void
    {
        foreach ($this->connectionsToTransact as $connection) {
            if (DB::connection($connection)->transactionLevel() > 0) {
                DB::connection($connection)->rollBack();
            }
        }
    }

    protected function migrateThreeDatabases(): void
    {
        $connections = [
            'isp_application' => 'database/migrations/application',
            'isp_core' => 'database/migrations/core',
            'freeradius' => 'database/migrations/radius',
        ];

        foreach ($connections as $connection => $path) {
            if (in_array($connection, static::$threeDatabasesMigrated, true)) {
                continue;
            }

            if (! is_dir(storage_path('testing'))) {
                mkdir(storage_path('testing'), 0775, true);
            }

            $this->artisan('migrate:fresh', [
                '--database' => $connection,
                '--path' => $path,
                '--force' => true,
            ])->assertExitCode(0);

            static::$threeDatabasesMigrated[] = $connection;
        }
    }
}
