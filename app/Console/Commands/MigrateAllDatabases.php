<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MigrateAllDatabases extends Command
{
    protected $signature = 'migrate:all {--fresh : Drop all tables and re-run all migrations}';
    protected $description = 'Migrate all databases (isp_application, isp_core, freeradius)';

    public function handle()
    {
        $databases = [
            'isp_application' => 'database/migrations/application',
            'isp_core' => 'database/migrations/core',
            'freeradius' => 'database/migrations/radius',
        ];

        $fresh = $this->option('fresh');

        foreach ($databases as $connection => $path) {
            $this->info("Processing {$connection}...");

            try {
                // Créer la base de données si elle n'existe pas
                $this->createDatabaseIfNotExists($connection);

                // Exécuter les migrations
                if ($fresh) {
                    $this->call('migrate:fresh', [
                        '--database' => $connection,
                        '--path' => $path,
                        '--force' => true,
                    ]);
                } else {
                    $this->call('migrate', [
                        '--database' => $connection,
                        '--path' => $path,
                        '--force' => true,
                    ]);
                }

                $this->info("✓ {$connection} migrated successfully!");
                
            } catch (\Exception $e) {
                $this->error("✗ Error with {$connection}: " . $e->getMessage());
            }
        }

        $this->info("\n✅ All databases migrated successfully!");
    }

    private function createDatabaseIfNotExists($connection)
    {
        $config = config("database.connections.{$connection}");
        $databaseName = $config['database'];

        // Récupérer la connexion sans spécifier de base de données
        $defaultConfig = $config;
        unset($defaultConfig['database']);
        
        try {
            $pdo = DB::connection($connection)->getPdo();
            
            // Vérifier si la base existe
            $query = "SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?";
            $exists = DB::select($query, [$databaseName]);
            
            if (empty($exists)) {
                // Créer la base de données
                DB::statement("CREATE DATABASE IF NOT EXISTS `{$databaseName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                $this->info("  ✓ Database '{$databaseName}' created");
            } else {
                $this->info("  ✓ Database '{$databaseName}' already exists");
            }
            
        } catch (\Exception $e) {
            $this->warn("  ⚠ Could not create database: " . $e->getMessage());
        }
    }
}