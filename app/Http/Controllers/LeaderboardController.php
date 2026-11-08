<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Badge;
use App\Enums\BadgeTier;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

final readonly class LeaderboardController
{
    private const int LIMIT = 50;

    public function index(Request $request): Response
    {
        $request->validate([
            'period' => ['nullable', Rule::in(['week', 'month', 'all'])],
        ]);

        $period = $request->string('period', 'week')->value();
        $since = match ($period) {
            'week' => now()->subWeek(),
            'month' => now()->subMonth(),
            default => null,
        };

        $query = User::query()
            ->whereNotNull('email_verified_at')
            ->withSum(['reputationEvents as gained' => fn (Builder $query) => $since === null ? $query : $query->where('created_at', '>=', $since)], 'amount')
            ->withCount(array_combine(
                array_map(fn (BadgeTier $tier): string => sprintf('badges as %s_badges', $tier->value), BadgeTier::cases()),
                array_map(fn (BadgeTier $tier): callable => fn (Builder $query) => $query->whereIn('badge', $this->badgesOf($tier)), BadgeTier::cases()),
            ));

        $users = ($since === null
            ? $query->where('reputation', '>', 1)->orderByDesc('reputation')
            : $query->whereHas('reputationEvents', fn (Builder $query) => $query->where('created_at', '>=', $since))->orderByDesc('gained')->orderByDesc('reputation'))
            ->limit(self::LIMIT)
            ->get()
            ->when($since !== null, fn (Collection $users): Collection => $users->filter(fn (User $user): bool => $this->integer($user->getAttribute('gained')) > 0));

        return Inertia::render('leaderboard/Index', [
            'period' => $period,
            'leaders' => $users->values()->map(fn (User $user, int $index): array => [
                'rank' => $index + 1,
                'id' => $user->id,
                'name' => $user->name,
                'reputation' => $user->reputation,
                'gained' => $this->integer($user->getAttribute('gained')),
                'badges' => [
                    'gold' => $this->integer($user->getAttribute('gold_badges')),
                    'silver' => $this->integer($user->getAttribute('silver_badges')),
                    'bronze' => $this->integer($user->getAttribute('bronze_badges')),
                ],
            ])->all(),
        ]);
    }

    private function integer(mixed $value): int
    {
        return is_numeric($value) ? (int) $value : 0;
    }

    /**
     * @return list<string>
     */
    private function badgesOf(BadgeTier $tier): array
    {
        return array_values(array_map(
            fn (Badge $badge): string => $badge->value,
            array_filter(Badge::cases(), fn (Badge $badge): bool => $badge->tier() === $tier),
        ));
    }
}
