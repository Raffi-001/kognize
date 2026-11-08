<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\NotificationKind;
use App\Models\Submission;
use App\Models\User;
use App\Models\UserNote;
use Illuminate\Support\Str;

final readonly class CreateUserNote
{
    public function __construct(private NotifyActivity $notify) {}

    public function handle(Submission $submission, User $user, string $text, ?string $label = null): UserNote
    {
        $note = $submission->userNotes()->create([
            'user_id' => $user->id,
            'annotation_id' => 'un-'.Str::lower(Str::random(12)),
            'label' => $label === null || mb_trim($label) === '' ? null : mb_trim($label),
            'text' => mb_trim($text),
        ]);

        $this->notify->handle(
            [$submission->user],
            NotificationKind::CommunityNoteAdded,
            $user,
            ['type' => 'submission', 'id' => $submission->id, 'title' => $submission->displayTitle()],
            route('submissions.show', $submission),
            $note->text,
        );

        return $note;
    }
}
