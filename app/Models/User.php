<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\NotificationKind;
use App\Enums\Plan;
use App\Enums\UrlSubmissionStatus;
use Carbon\CarbonInterface;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property-read string $id
 * @property-read string $name
 * @property-read string $email
 * @property-read CarbonInterface|null $email_verified_at
 * @property-read string $password
 * @property-read Plan $plan
 * @property-read bool $is_admin
 * @property-read string|null $referrer_id
 * @property-read list<string>|null $muted_notifications
 * @property-read bool $notification_digest
 * @property-read string|null $remember_token
 * @property-read string|null $two_factor_secret
 * @property-read string|null $two_factor_recovery_codes
 * @property-read CarbonInterface|null $two_factor_confirmed_at
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 * @property-read Collection<int, Submission> $submissions
 * @property-read Collection<int, Invitation> $invitations
 * @property-read User|null $referrer
 * @property-read Collection<int, User> $referees
 * @property-read Collection<int, UrlSubmission> $urlSubmissions
 */
#[Hidden([
    'password',
    'remember_token',
    'two_factor_secret',
    'two_factor_recovery_codes',
])]
final class User extends Authenticatable implements FilamentUser, MustVerifyEmail, PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use HasUuids;
    use Notifiable;
    use PasskeyAuthenticatable;
    use TwoFactorAuthenticatable;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'plan' => 'standard',
        'is_admin' => false,
        'referrer_id' => null,
        'muted_notifications' => null,
        'notification_digest' => false,
    ];

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'id' => 'string',
            'name' => 'string',
            'email' => 'string',
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'plan' => Plan::class,
            'is_admin' => 'boolean',
            'referrer_id' => 'string',
            'muted_notifications' => 'array',
            'notification_digest' => 'boolean',
            'remember_token' => 'string',
            'two_factor_secret' => 'string',
            'two_factor_recovery_codes' => 'string',
            'two_factor_confirmed_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<Submission, $this>
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    /**
     * @return HasMany<Invitation, $this>
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class, 'inviter_id');
    }

    /**
     * The user whose invitation code this user signed up with.
     *
     * @return BelongsTo<User, $this>
     */
    public function referrer(): BelongsTo
    {
        return $this->belongsTo(self::class, 'referrer_id');
    }

    /**
     * Users who signed up with one of this user's invitation codes.
     *
     * @return HasMany<User, $this>
     */
    public function referees(): HasMany
    {
        return $this->hasMany(self::class, 'referrer_id');
    }

    /**
     * @return HasMany<UrlSubmission, $this>
     */
    public function urlSubmissions(): HasMany
    {
        return $this->hasMany(UrlSubmission::class);
    }

    public function wantsNotification(NotificationKind $kind): bool
    {
        return ! in_array($kind->value, $this->muted_notifications ?? [], true);
    }

    public function submissionsUsedToday(): int
    {
        $submissions = $this->submissions()
            ->where('created_at', '>=', today())
            ->count();

        $queuedUrls = $this->urlSubmissions()
            ->where('created_at', '>=', today())
            ->whereIn('status', [UrlSubmissionStatus::Pending, UrlSubmissionStatus::Processing])
            ->count();

        return $submissions + $queuedUrls;
    }

    public function remainingSubmissionsToday(): int
    {
        return max(0, $this->plan->dailySubmissions() - $this->submissionsUsedToday());
    }

    public function remainingInvitations(): int
    {
        return max(0, $this->plan->invitations() - $this->invitations()->count());
    }

    /**
     * Only administrators may use the moderation panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_admin && $this->hasVerifiedEmail();
    }
}
