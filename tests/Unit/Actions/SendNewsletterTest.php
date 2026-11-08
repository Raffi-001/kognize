<?php

declare(strict_types=1);

use App\Actions\SendNewsletter;
use App\Mail\NewsletterMail;
use App\Models\Newsletter;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

it('queues the newsletter for each user and records them once', function (): void {
    Mail::fake();

    $newsletter = Newsletter::factory()->create();
    $users = User::factory()->count(2)->create();

    $count = resolve(SendNewsletter::class)->handle($newsletter, $users);
    resolve(SendNewsletter::class)->handle($newsletter, [$users[0]]);

    expect($count)->toBe(2)
        ->and($newsletter->recipients()->count())->toBe(2);

    Mail::assertQueued(NewsletterMail::class, 3);
    Mail::assertQueued(NewsletterMail::class, fn (NewsletterMail $mail): bool => $mail->hasTo($users[1]->email) && $mail->newsletter->is($newsletter));
});
