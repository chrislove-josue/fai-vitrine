<?php

use App\Models\ApiClient;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('les mots de passe du fichier .env.example sont vides', function () {
    $env = file_get_contents(base_path('.env.example'));

    expect($env)->toContain('DB_APPLICATION_PASSWORD=');
    expect($env)->toContain('DB_CORE_PASSWORD=');
    expect($env)->toContain('DB_RADIUS_PASSWORD=');
    expect($env)->not->toContain('Narcisse');
});

test('le hash du secret d\'un api_client n\'est jamais le secret en clair', function () {
    $client = ApiClient::create([
        'name' => 'Test Client',
        'client_id' => 'test-client-'.uniqid(),
        'secret_hash' => Hash::make('cle-en-clair'),
    ]);

    $fresh = ApiClient::find($client->id);

    expect($fresh->secret_hash)->not->toBe('cle-en-clair');
    expect(Hash::check('cle-en-clair', $fresh->secret_hash))->toBeTrue();
});

test('les mots de passe utilisateurs sont stockés via le casting hashed', function () {
    $user = User::factory()->create(['password' => 'MotDePasseTropSecret']);

    $raw = $user->getRawOriginal('password');

    expect($raw)->not->toBe('MotDePasseTropSecret');
    expect(Hash::isHashed($raw))->toBeTrue();
});

test('la connexion isp_application lit ses identifiants depuis les variables d\'environnement', function () {
    $connection = config('database.connections.isp_application');

    expect($connection['password'])->toBe(env('DB_APPLICATION_PASSWORD', ''));
    expect($connection['username'])->toBe(env('DB_APPLICATION_USERNAME', 'root'));
});
