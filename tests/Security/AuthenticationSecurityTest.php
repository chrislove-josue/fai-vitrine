<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

function authSecUser(string $role = 'client', string $status = 'active'): User
{
    $user = User::factory()->create(['status' => $status, 'password' => 'MotDePasse1!']);
    $user->assignRole($role);

    return $user;
}

test('le mot de passe est stocké haché et jamais en clair', function () {
    $user = authSecUser();

    expect(Hash::isHashed($user->password))->toBeTrue();
    expect($user->password)->not->toBe('MotDePasse1!');
    expect($user->toArray())->not->toHaveKey('password');
});

test('la connexion est protégée contre le brute-force (throttle)', function () {
    $user = authSecUser();

    for ($i = 0; $i < 5; $i++) {
        $this->post('/login', ['email' => $user->email, 'password' => 'mauvais']);
    }

    $this->post('/login', ['email' => $user->email, 'password' => 'mauvais'])
        ->assertStatus(429);
});

test('un compte bloqué est refusé même avec de bons identifiants', function () {
    $user = authSecUser(status: 'blocked');

    $this->post('/login', ['email' => $user->email, 'password' => 'MotDePasse1!'])
        ->assertSessionHasErrors('email');

    expect(auth()->check())->toBeFalse();
});

test('le middleware is_active déconnecte un utilisateur devenu inactif', function () {
    $user = authSecUser();
    $this->actingAs($user);

    $user->update(['status' => 'inactive']);

    $this->get(route('dashboard.index'))->assertRedirect(route('login'));
    expect(auth()->check())->toBeFalse();
});

test('un client ne peut forcer l\'accès admin même en manipulant les routes', function () {
    $user = authSecUser(role: 'client');

    $this->actingAs($user)->get('/admin')->assertForbidden();
});

test('un client sans rôle affecté n\'accède pas à l\'administration', function () {
    $user = User::factory()->create(['status' => 'active']);

    $this->actingAs($user)->get(route('admin.index'))->assertForbidden();
});

test('la connexion avec « remember me » pose un cookie persistant', function () {
    $user = authSecUser();

    $this->post('/login', ['email' => $user->email, 'password' => 'MotDePasse1!', 'remember' => '1']);

    $this->assertAuthenticated();
    expect($user->fresh()->remember_token)->not->toBeNull();
});

test('le token de réinitialisation n\'est pas réutilisable après usage', function () {
    $user = authSecUser();
    $token = app('auth.password.broker')->createToken($user);

    $this->post('/password/reset', [
        'token' => $token,
        'email' => $user->email,
        'password' => 'NouveauMotDePasse!',
        'password_confirmation' => 'NouveauMotDePasse!',
    ])->assertSessionHas('status');

    $this->post('/password/reset', [
        'token' => $token,
        'email' => $user->email,
        'password' => 'EncoreUnAutre!',
        'password_confirmation' => 'EncoreUnAutre!',
    ])->assertSessionHasErrors('email');

    expect(Hash::check('NouveauMotDePasse!', $user->fresh()->password))->toBeTrue();
});
