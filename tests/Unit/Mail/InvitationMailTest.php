<?php

declare(strict_types=1);

use App\Mail\InvitationMail;
use App\Models\Invitation;
use App\Models\User;

it('builds the invitation mail', function (): void {
    $inviter = User::factory()->create(['name' => 'Anna']);
    $invitation = Invitation::factory()->for($inviter, 'inviter')->create(['code' => '12345']);

    $mail = new InvitationMail($invitation, $inviter);

    $mail->assertHasSubject(sprintf('Anna invited you to %s', config('app.name')))
        ->assertSeeInHtml('Anna')
        ->assertSeeInHtml('12345')
        ->assertSeeInHtml(route('register', ['invitation' => '12345']), false)
        ->assertSeeInText(route('register', ['invitation' => '12345']));

    expect($mail->content()->with['registrationUrl'])->toBe(route('register', ['invitation' => '12345']));
});
