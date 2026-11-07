<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Report;
use App\Models\User;

final readonly class CreateReport
{
    public function handle(User $user, string $reportableType, int $reportableId, string $reason): Report
    {
        return Report::query()->firstOrCreate([
            'user_id' => $user->id,
            'reportable_type' => $reportableType,
            'reportable_id' => $reportableId,
        ], [
            'reason' => mb_trim($reason),
        ]);
    }
}
