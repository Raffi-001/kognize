<?php

declare(strict_types=1);

arch()->preset()->php();
// Filament resources and pages must override protected framework methods and properties.
arch()->preset()->strict()->ignoring([
    'App\\Filament',
]);
// Filament panel providers are named after the panel, without the ServiceProvider suffix.
arch()->preset()->laravel()->ignoring([
    'App\\Providers\\Filament',
]);
arch()->preset()->security()->ignoring([
    'assert',
]);

arch('controllers')
    ->expect('App\Http\Controllers')
    ->not->toBeUsed();

//
