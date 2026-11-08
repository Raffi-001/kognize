<?php

declare(strict_types=1);

it('has welcome page', function (): void {
    $page = visit('/');

    $page->assertSee('Read between the')
        ->assertSee('Kognize')
        ->assertDontSee('Start analyzing')
        ->assertSeeIn('@hero-cta', 'Redeem an Invitation')
        ->click('@hero-cta')
        ->assertPathIs('/register')
        ->assertNoJavaScriptErrors();
});

it('explains each highlight of the interactive demo', function (): void {
    $page = visit('/')->resize(1280, 900);

    $page->assertSeeIn('@welcome-demo-note', 'No true Scotsman')
        ->click('@demo-mark-rhetoric')
        ->assertSeeIn('@welcome-demo-note', 'Manufactured urgency')
        ->click('[aria-label="Previous finding"]')
        ->assertSeeIn('@welcome-demo-note', 'Which baristas')
        ->screenshot(filename: 'zz-welcome')
        ->assertNoJavaScriptErrors();
});
