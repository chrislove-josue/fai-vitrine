<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/**
 * Génère un `uuid` distinct de la clé primaire auto-incrémentée.
 *
 * Contrairement au trait Laravel `HasUuids` (qui transforme la clé
 * primaire en uuid dans Laravel 13), ce trait conserve `id` BIGINT
 * et alimente la colonne métier `uuid` utilisée comme référence
 * logique entre les trois bases (sans FK inter-base).
 */
trait GeneratesUuid
{
    public static function bootGeneratesUuid(): void
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid7();
            }
        });
    }
}
