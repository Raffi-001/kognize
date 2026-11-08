<?php

declare(strict_types=1);

namespace App\Actions;

use App\Mail\NewsletterMail;
use App\Models\Newsletter;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

final readonly class SendNewsletter
{
    /**
     * Queue the newsletter for each user and record them as recipients.
     *
     * @param  iterable<User>  $users
     */
    public function handle(Newsletter $newsletter, iterable $users): int
    {
        $sent = [];

        foreach ($users as $user) {
            Mail::to($user)->queue(new NewsletterMail($newsletter, $user));

            $sent[] = $user->id;
        }

        $newsletter->recipients()->syncWithoutDetaching($sent);

        return count($sent);
    }
}
