<?php

declare(strict_types=1);

use App\Models\Invitation;
use App\Rules\ValidInvitationCode;

/**
 * @param  array<string, mixed>  $data
 */
function invitationCodeError(mixed $code, array $data): ?string
{
    $message = null;

    new ValidInvitationCode()
        ->setData($data)
        ->validate('invitation_code', $code, function (string $error) use (&$message): void {
            $message = $error;
        });

    return $message;
}

it('passes for an unused code issued to the email', function (): void {
    Invitation::factory()->create(['code' => '12345', 'email' => 'test@example.com']);

    expect(invitationCodeError('12345', ['email' => 'TEST@example.com']))->toBeNull();
});

it('fails for unknown, used or invalid codes', function (mixed $code): void {
    Invitation::factory()->used()->create(['code' => '11111', 'email' => 'test@example.com']);

    expect(invitationCodeError($code, ['email' => 'test@example.com']))
        ->toBe('This invitation code is invalid or has already been used.');
})->with(['unknown' => '99999', 'used' => '11111', 'not a string' => [['12345']]]);

it('fails for codes issued to another email', function (array $data): void {
    Invitation::factory()->create(['code' => '12345', 'email' => 'someone@example.com']);

    expect(invitationCodeError('12345', $data))
        ->toBe('This invitation code was issued for a different email address.');
})->with([
    'other email' => [['email' => 'test@example.com']],
    'missing email' => [[]],
]);
