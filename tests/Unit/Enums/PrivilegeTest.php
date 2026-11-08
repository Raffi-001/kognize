<?php

declare(strict_types=1);

use App\Enums\Privilege;

it('describes every privilege', function (Privilege $privilege): void {
    config(['kognize.privileges.'.$privilege->value => 42]);

    expect($privilege->threshold())->toBe(42)
        ->and($privilege->label())->toBeString()->not->toBeEmpty()
        ->and($privilege->description())->toEndWith('.');
})->with(Privilege::cases());

it('lists privileges in the order they are earned', function (): void {
    expect(Privilege::ladder())->toBe([
        Privilege::VoteUp,
        Privilege::VoteDown,
        Privilege::ExtraAnalysis,
        Privilege::ExtraInvitations,
        Privilege::Trusted,
    ]);

    config(['kognize.privileges.vote_down' => 5000]);

    expect(Privilege::ladder()[4])->toBe(Privilege::VoteDown);
});

it('grants extra analyses and invitations', function (Privilege $privilege, int $analyses, int $invitations): void {
    expect($privilege->bonusDailySubmissions())->toBe($analyses)
        ->and($privilege->bonusInvitations())->toBe($invitations);
})->with([
    [Privilege::VoteUp, 0, 0],
    [Privilege::VoteDown, 0, 0],
    [Privilege::ExtraAnalysis, 1, 0],
    [Privilege::ExtraInvitations, 0, 3],
    [Privilege::Trusted, 2, 0],
]);
