<?php

use App\Models\Customer;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

function authUser(array $attributes = [], string $role = 'client'): User
{
    $user = User::factory()->create(array_merge([
        'status' => 'active',
        'password' => 'MotDePasse1!',
    ], $attributes));
    $user->assignRole($role);

    return $user;
}

test('un utilisateur client est redirigé vers l\'espace client après connexion', function () {
    $user = authUser(role: 'client');

    $this->post('/login', ['email' => $user->email, 'password' => 'MotDePasse1!'])
        ->assertRedirect(route('dashboard.index'));

    expect($user->fresh()->last_login_at)->not->toBeNull();
});

test('un utilisateur staff est redirigé vers l\'administration après connexion', function () {
    $user = authUser(role: 'admin');

    $this->post('/login', ['email' => $user->email, 'password' => 'MotDePasse1!'])
        ->assertRedirect(route('admin.index'));
});

test('la connexion échoue avec des identifiants incorrects', function () {
    $user = authUser();

    $this->post('/login', ['email' => $user->email, 'password' => 'mauvais-mot-de-passe'])
        ->assertSessionHasErrors('email')
        ->assertRedirect();

    expect(auth()->check())->toBeFalse();
});

test('un utilisateur inactif ne peut pas se connecter', function () {
    $user = authUser(['status' => 'inactive']);

    $this->post('/login', ['email' => $user->email, 'password' => 'MotDePasse1!'])
        ->assertSessionHasErrors('email');

    expect(auth()->check())->toBeFalse();
});

test('la déconnexion termine la session', function () {
    $user = authUser();
    $this->actingAs($user);

    $this->post('/logout')
        ->assertRedirect(route('login'));

    expect(auth()->check())->toBeFalse();
});

test('un client ne peut pas accéder à l\'administration', function () {
    $user = authUser(role: 'client');

    $this->actingAs($user)->get(route('admin.index'))->assertForbidden();
});

test('un staff ne peut pas accéder à l\'espace client', function () {
    $user = authUser(role: 'support');

    $this->actingAs($user)->get(route('dashboard.index'))->assertForbidden();
});

test('un utilisateur non authentifié est redirigé vers la page de connexion', function () {
    $this->get(route('dashboard.index'))->assertRedirect(route('login'));
    $this->get(route('admin.index'))->assertRedirect(route('login'));
});

test('l\'espace client affiche les données du client rattaché', function () {
    $customer = Customer::factory()->create(['first_name' => 'Alice', 'last_name' => 'Dupont']);
    $user = authUser(['customer_uuid' => $customer->uuid], role: 'client');

    $this->actingAs($user)->get(route('dashboard.index'))
        ->assertOk()
        ->assertSee('Alice Dupont');
});

test('le login affiche les données du client rattaché', function () {
    $customer = Customer::factory()->create(['first_name' => 'Bob', 'last_name' => 'Martin']);
    $user = authUser(['customer_uuid' => $customer->uuid], role: 'client');

    $this->actingAs($user)->get(route('dashboard.index'))
        ->assertSee('Bob Martin');
});

test('l\'administration Filament affiche le panneau', function () {
    $user = authUser(role: 'admin');

    $this->actingAs($user)->get('/admin')
        ->assertOk()
        ->assertSee('Jenysas ISP')
        ->assertSee('Clients')
        ->assertSee('Abonnements');
});

test('la réinitialisation du mot de passe envoie un lien', function () {
    $user = authUser();

    $this->post('/password/email', ['email' => $user->email])
        ->assertSessionHas('status');
});

test('la réinitialisation du mot de passe modifie le mot de passe', function () {
    $user = authUser();
    $token = app('auth.password.broker')->createToken($user);

    $this->post('/password/reset', [
        'token' => $token,
        'email' => $user->email,
        'password' => 'NouveauMotDePasse!',
        'password_confirmation' => 'NouveauMotDePasse!',
    ])->assertSessionHas('status');

    expect(Hash::check('NouveauMotDePasse!', $user->fresh()->password))->toBeTrue();
});

test('la commande user:create crée un utilisateur avec mot de passe haché', function () {
    $this->artisan('user:create', [
        '--name' => 'Test User',
        '--email' => 'test-create@example.com',
        '--password' => 'MotDePasse1!',
        '--role' => 'admin',
    ])->assertExitCode(0);

    $user = User::where('email', 'test-create@example.com')->first();
    expect($user)->not->toBeNull();
    expect($user->hasRole('admin'))->toBeTrue();
    expect(Hash::isHashed($user->password))->toBeTrue();
    expect($user->password)->not->toBe('MotDePasse1!');
});

test('la commande user:create refuse un rôle inconnu', function () {
    $this->artisan('user:create', [
        '--name' => 'Bad Role',
        '--email' => 'bad-role@example.com',
        '--password' => 'MotDePasse1!',
        '--role' => 'nonexistent',
    ])->assertFailed();
});

test('la commande user:create rattache un client via --customer-number', function () {
    $customer = Customer::factory()->create(['customer_number' => 'CUS-TEST001']);

    $this->artisan('user:create', [
        '--name' => 'Client Lié',
        '--email' => 'lie@example.com',
        '--password' => 'MotDePasse1!',
        '--role' => 'client',
        '--customer-number' => 'CUS-TEST001',
    ])->assertExitCode(0);

    expect(User::where('email', 'lie@example.com')->first()->customer_uuid)->toBe($customer->uuid);
});

test('la commande user:create refuse un numéro client inconnu', function () {
    $this->artisan('user:create', [
        '--name' => 'Client Fantôme',
        '--email' => 'fantome@example.com',
        '--password' => 'MotDePasse1!',
        '--role' => 'client',
        '--customer-number' => 'CUS-INCONNU',
    ])->assertFailed();

    expect(User::where('email', 'fantome@example.com')->count())->toBe(0);
});

test('la commande user:link-customer relie un utilisateur existant', function () {
    $customer = Customer::factory()->create(['customer_number' => 'CUS-LINK001']);
    $user = authUser(['email' => 'client-existant@example.com']);

    $this->artisan('user:link-customer', [
        'email' => $user->email,
        '--customer-number' => 'CUS-LINK001',
    ])->assertExitCode(0);

    expect($user->fresh()->customer_uuid)->toBe($customer->uuid);
});

test('la commande user:link-customer refuse un client inexistant', function () {
    $user = authUser(['email' => 'client-existant2@example.com']);

    $this->artisan('user:link-customer', [
        'email' => $user->email,
        '--customer-number' => 'CUS-INCONNU',
    ])->assertFailed();

    expect($user->fresh()->customer_uuid)->toBeNull();
});
