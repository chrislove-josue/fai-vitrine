<?php

use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Resources\Invoices\InvoiceResource;
use App\Filament\Resources\NetworkAccounts\NetworkAccountResource;
use App\Filament\Resources\NetworkProfiles\NetworkProfileResource;
use App\Filament\Resources\Offers\OfferResource;
use App\Filament\Resources\Payments\PaymentResource;
use App\Filament\Resources\Subscriptions\SubscriptionResource;
use App\Models\AuditLog;
use App\Models\Offer;
use App\Models\OfferPrice;
use App\Models\User;
use App\Support\AdminAudit;

function adminUser(array $attributes = [], string $role = 'admin'): User
{
    $user = User::factory()->create(array_merge([
        'status' => 'active',
        'password' => 'MotDePasse1!',
    ], $attributes));
    $user->assignRole($role);

    return $user;
}

test('le panneau administratif est accessible aux staff', function () {
    $user = adminUser(role: 'admin');

    $this->actingAs($user)->get('/admin')
        ->assertOk()
        ->assertSee('Jenysas ISP');
});

test('un client ne peut pas accéder au panneau administratif', function () {
    $user = adminUser(role: 'client');

    $this->actingAs($user)->get('/admin')->assertForbidden();
});

test('un utilisateur non authentifié est redirigé par le panneau', function () {
    $this->get('/admin')->assertRedirect();
});

test('un staff peut lister toutes les ressources', function () {
    $user = adminUser(role: 'admin');
    $this->actingAs($user);

    foreach (['customers', 'offers', 'network-profiles', 'subscriptions', 'network-accounts', 'invoices', 'payments'] as $slug) {
        $this->get('/admin/'.$slug)->assertOk();
    }
});

test('la liste des offres s\'affiche avec un prix courant', function () {
    $user = adminUser(role: 'admin');
    $this->actingAs($user);

    $offer = Offer::factory()->create(['name' => 'Offre Pro']);
    OfferPrice::create([
        'offer_id' => $offer->id,
        'amount' => 12_000,
        'currency' => 'XOF',
        'starts_at' => now()->subDay(),
        'ends_at' => null,
    ]);

    $this->get('/admin/offers')
        ->assertOk()
        ->assertSee('Offre Pro')
        ->assertSee('12 000 XOF');
});

test('les ressources respectent les permissions par rôle', function () {
    $admin = adminUser(role: 'admin');
    $this->actingAs($admin);

    foreach ([CustomerResource::class, OfferResource::class, NetworkProfileResource::class, SubscriptionResource::class, NetworkAccountResource::class, InvoiceResource::class, PaymentResource::class] as $resource) {
        expect($resource::canViewAny())->toBeTrue();
    }

    $commercial = adminUser(role: 'commercial');
    $this->actingAs($commercial);

    expect(CustomerResource::canViewAny())->toBeTrue();
    expect(PaymentResource::canViewAny())->toBeFalse();
    expect(NetworkAccountResource::canViewAny())->toBeFalse();
    expect(PaymentResource::canCreate())->toBeFalse();
});

test('un staff sans permission ne peut pas ouvrir une ressource', function () {
    $commercial = adminUser(role: 'commercial');
    $this->actingAs($commercial);

    $this->get('/admin/payments')->assertForbidden();
});

test('AdminAudit journalise les actions d\'administration', function () {
    $user = adminUser(role: 'admin');
    $this->actingAs($user)->get('/admin');

    AdminAudit::log('test.action', null, null, ['cle' => 'valeur']);

    $entry = AuditLog::query()->where('action', 'test.action')->first();

    expect($entry)->not->toBeNull();
    expect($entry->user_uuid)->toBe($user->uuid);
    expect($entry->metadata)->toBe(['cle' => 'valeur']);
});
