<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Submission;
use App\Models\User;
use App\Models\UserNote;
use Illuminate\Support\Str;

final readonly class CreateUserNote
{
    public function handle(Submission $submission, User $user, string $text, ?string $label = null): UserNote
    {
        return $submission->userNotes()->create([
            'user_id' => $user->id,
            'annotation_id' => 'un-'.Str::lower(Str::random(12)),
            'label' => $label === null || mb_trim($label) === '' ? null : mb_trim($label),
            'text' => mb_trim($text),
        ]);
    }
}
