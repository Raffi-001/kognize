<?php

declare(strict_types=1);

namespace App\Actions;

use App\Jobs\ProcessUrlSubmissionJob;
use App\Models\UrlSubmission;
use App\Models\User;

final readonly class CreateUrlSubmission
{
    public function handle(User $user, string $url): UrlSubmission
    {
        $urlSubmission = $user->urlSubmissions()->create(['url' => $url]);

        dispatch(new ProcessUrlSubmissionJob($urlSubmission));

        return $urlSubmission;
    }
}
