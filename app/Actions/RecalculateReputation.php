<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\NotificationKind;
use App\Enums\Privilege;
use App\Models\User;

/**
 * Sums the reputation ledger into the user's cached reputation, tells them
 * about privileges they just unlocked and awards reputation badges.
 */
final readonly class RecalculateReputation
{
    public function __construct(
        private NotifyActivity $notify,
        private AwardBadges $badges,
    ) {}

    public function handle(User $user): User
    {
        $before = $user->reputation;
        $after = max(1, 1 + (int) $user->reputationEvents()->sum('amount'));

        if ($after !== $before) {
            $user->update(['reputation' => $after]);
        }

        foreach (Privilege::ladder() as $privilege) {
            if ($before < $privilege->threshold() && $after >= $privilege->threshold()) {
                $this->notify->handle(
                    [$user],
                    NotificationKind::PrivilegeUnlocked,
                    null,
                    ['type' => 'privilege', 'id' => $privilege->value, 'title' => $privilege->label()],
                    route('profiles.show', $user),
                    $privilege->description(),
                );
            }
        }

        $this->badges->handle($user);

        return $user;
    }
}
