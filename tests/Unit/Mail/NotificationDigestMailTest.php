<?php

declare(strict_types=1);

use App\Mail\NotificationDigestMail;
use App\Models\User;

it('builds the daily notification summary', function (): void {
    $mail = new NotificationDigestMail(User::factory()->create(), [
        ['headline' => 'Ani commented on “Coffee ad”', 'excerpt' => 'Nice catch'],
        ['headline' => 'Your analysis of “Coffee ad” is ready', 'excerpt' => null],
    ]);

    $mail->assertHasSubject(sprintf('2 new on %s', config('app.name')))
        ->assertSeeInHtml('Ani commented on')
        ->assertSeeInHtml('Nice catch')
        ->assertSeeInHtml('Your analysis of')
        ->assertSeeInHtml(route('notifications.index'), false)
        ->assertSeeInHtml(route('notification-preferences.edit'), false);
});
