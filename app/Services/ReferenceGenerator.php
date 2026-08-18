<?php

namespace App\Services;

use Illuminate\Support\Str;

/**
 * Génère les numéros métier uniques du système.
 */
class ReferenceGenerator
{
    public static function customerNumber(): string
    {
        return 'CUS-'.strtoupper(Str::random(8));
    }

    public static function subscriptionNumber(): string
    {
        return 'SUB-'.strtoupper(Str::random(8));
    }

    public static function invoiceNumber(): string
    {
        return 'INV-'.date('Y').'-'.strtoupper(Str::random(6));
    }

    public static function paymentReference(): string
    {
        return 'PAY-'.date('Ymd').'-'.strtoupper(Str::random(8));
    }

    public static function creditNoteNumber(): string
    {
        return 'CN-'.date('Y').'-'.strtoupper(Str::random(6));
    }
}
