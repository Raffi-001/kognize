<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\AnalysisNote;
use App\Models\Comment;
use App\Models\User;
use App\Models\UserNote;

final readonly class CreateComment
{
    public function handle(AnalysisNote|UserNote $commentable, User $user, string $text): Comment
    {
        return $commentable->comments()->create([
            'user_id' => $user->id,
            'text' => mb_trim($text),
        ]);
    }
}
