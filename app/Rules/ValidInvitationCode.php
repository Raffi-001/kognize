<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\Invitation;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

final class ValidInvitationCode implements DataAwareRule, ValidationRule
{
    /**
     * @var array<string, mixed>
     */
    private array $data = [];

    /**
     * @param  array<string, mixed>  $data
     */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $invitation = Invitation::query()
            ->where('code', is_string($value) ? $value : '')
            ->whereNull('used_at')
            ->first();

        if ($invitation === null) {
            $fail('This invitation code is invalid or has already been used.');

            return;
        }

        if ($invitation->isOpen()) {
            return;
        }

        $email = $this->data['email'] ?? null;

        if (! is_string($email) || mb_strtolower((string) $invitation->email) !== mb_strtolower($email)) {
            $fail('This invitation code was issued for a different email address.');
        }
    }
}
