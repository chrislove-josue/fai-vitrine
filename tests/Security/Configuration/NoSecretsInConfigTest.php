<?php

use Illuminate\Support\Facades\File;

test('les fichiers de configuration ne contiennent aucun mot de passe en clair', function () {
    $configDir = config_path();
    $sensitivePatterns = [
        'DB_PASSWORD=' => 'mot de passe en clair',
        'DB_APPLICATION_PASSWORD=' => 'mot de passe en clair',
        'DB_CORE_PASSWORD=' => 'mot de passe en clair',
        'DB_RADIUS_PASSWORD=' => 'mot de passe en clair',
    ];

    foreach (File::files($configDir) as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }

        $content = $file->getContents();
        expect($content)->not->toMatch('/password\s*=>\s*[\'"][^\'"]+[\'"]/', 'Le fichier '.$file->getFilename().' expose un mot de passe.');
    }
});

test('les identifiants ne sont pas stockés dans les variables d\'environnement du repository', function () {
    $envExample = base_path('.env.example');

    expect(File::exists($envExample))->toBeTrue();
    expect(File::get($envExample))->not->toContain('Narcisse@62');
});

test('les secrets du fichier .env local ne sont pas commités', function () {
    $gitignore = File::get(base_path('.gitignore'));

    expect($gitignore)->toContain('.env');
});
