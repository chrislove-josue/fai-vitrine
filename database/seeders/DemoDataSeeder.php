<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\ExternalSystem;
use App\Models\NetworkAccount;
use App\Models\NetworkProfile;
use App\Models\NetworkSession;
use App\Models\Offer;
use App\Models\OfferPrice;
use App\Models\Subscription;
use App\Services\BillingService;
use Illuminate\Database\Seeder;

/**
 * Données de démonstration pour la validation manuelle de l'étape 10
 * (espace client) : un client, une offre, un abonnement, des sessions
 * réseau et une facture à régler. Idempotent.
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $system = ExternalSystem::firstOrCreate(
            ['code' => 'PAYGW'],
            [
                'name' => 'Passerelle de paiement (démo)',
                'type' => 'payment_gateway',
                'base_url' => 'https://pay.demo.local',
                'status' => 'active',
                'configuration' => ['webhook_secret' => 'demo-webhook-secret'],
            ],
        );

        $profile = NetworkProfile::firstOrCreate(
            ['code' => 'NP-DEMO'],
            [
                'name' => 'Fibre 100 Mbps (démo)',
                'download_speed' => 100_000_000,
                'upload_speed' => 50_000_000,
                'status' => 'active',
            ],
        );

        $offer = Offer::firstOrCreate(
            ['code' => 'OFF-DEMO'],
            [
                'name' => 'Fibre 100 Mbps',
                'description' => 'Offre de démonstration',
                'status' => 'active',
                'duration_days' => 30,
                'network_profile_id' => $profile->id,
                'activation_fee' => 0,
                'currency' => 'XOF',
            ],
        );

        OfferPrice::firstOrCreate(
            ['offer_id' => $offer->id, 'amount' => 15_000],
            ['currency' => 'XOF', 'starts_at' => now()->subYear(), 'ends_at' => null],
        );

        $customer = Customer::firstOrCreate(
            ['customer_number' => 'CUS-DEMO001'],
            [
                'type' => 'individual',
                'status' => 'active',
                'first_name' => 'Alice',
                'last_name' => 'Dupont',
                'email' => 'alice.dupont@example.com',
                'phone' => '+2250700000001',
            ],
        );

        $account = NetworkAccount::firstOrCreate(
            ['username' => 'alice-demo'],
            [
                'customer_id' => $customer->id,
                'authentication_type' => 'pap',
                'status' => 'active',
                'mac_auth_enabled' => false,
            ],
        );

        $subscription = Subscription::firstOrCreate(
            ['subscription_number' => 'SUB-DEMO001'],
            [
                'customer_id' => $customer->id,
                'offer_id' => $offer->id,
                'status' => 'active',
                'starts_at' => now()->subDays(10),
                'expires_at' => now()->addDays(20),
                'activated_at' => now()->subDays(10),
                'next_renewal_at' => now()->addDays(20),
                'auto_renew' => true,
                'price' => 15_000,
                'currency' => 'XOF',
            ],
        );

        if (NetworkSession::where('network_account_uuid', $account->uuid)->count() === 0) {
            foreach (range(1, 4) as $day) {
                NetworkSession::create([
                    'network_account_uuid' => $account->uuid,
                    'username' => $account->username,
                    'session_id' => 'SES-DEMO-'.$day,
                    'ip_address' => '192.168.10.'.$day,
                    'started_at' => now()->subDays($day)->setTime(8, random_int(0, 59)),
                    'ended_at' => now()->subDays($day)->setTime(random_int(18, 22), random_int(0, 59)),
                    'session_seconds' => random_int(3_600, 14_400),
                    'bytes_in' => random_int(50, 400) * 1024 * 1024,
                    'bytes_out' => random_int(5, 40) * 1024 * 1024,
                    'terminate_cause' => 'User-Request',
                ]);
            }
        }

        $hasUnpaidInvoice = $customer->invoices()
            ->whereIn('status', ['issued', 'overdue', 'partially_paid'])
            ->exists();

        if (! $hasUnpaidInvoice) {
            app(BillingService::class)->issueInvoiceForSubscription($subscription);
        }

        $this->command?->info('Données de démo prêtes. Paiement à confirmer via payment:simulate-confirm <reference>.');
    }
}
