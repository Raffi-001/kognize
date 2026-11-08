<?php

declare(strict_types=1);

use App\Filament\Resources\Invitations\Pages\ListInvitations;
use App\Mail\InvitationMail;
use App\Models\Invitation;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\Mail;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->admin = User::factory()->admin()->create();

    $this->actingAs($this->admin);
});

it('lists codes and filters them by status', function (): void {
    $unused = Invitation::factory()->create(['email' => null]);
    $used = Invitation::factory()->used()->create();

    livewire(ListInvitations::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$unused, $used])
        ->assertTableColumnStateSet('status', 'Unused', $unused)
        ->assertTableColumnStateSet('status', 'Used', $used)
        ->assertTableColumnStateSet('invitee.name', $used->invitee?->name, $used)
        ->filterTable('status', 'unused')
        ->assertCanSeeTableRecords([$unused])
        ->assertCanNotSeeTableRecords([$used])
        ->filterTable('status', 'used')
        ->assertCanSeeTableRecords([$used])
        ->assertCanNotSeeTableRecords([$unused])
        ->resetTableFilters()
        ->assertCanSeeTableRecords([$unused, $used]);
});

it('generates open codes in bulk', function (): void {
    Mail::fake();

    livewire(ListInvitations::class)
        ->callAction('generate', ['email' => '', 'count' => 3])
        ->assertHasNoActionErrors()
        ->assertNotified('3 codes created');

    expect($this->admin->invitations()->whereNull('email')->count())->toBe(3);

    Mail::assertNothingQueued();
});

it('generates one code for an email address and mails it', function (): void {
    Mail::fake();

    livewire(ListInvitations::class)
        ->callAction('generate', ['email' => 'friend@example.com', 'count' => 5])
        ->assertNotified('Invitation sent to friend@example.com');

    expect($this->admin->invitations()->pluck('email')->all())->toBe(['friend@example.com']);

    Mail::assertQueued(InvitationMail::class);
});

it('validates the number of codes', function (): void {
    livewire(ListInvitations::class)
        ->callAction('generate', ['email' => '', 'count' => 51])
        ->assertHasActionErrors(['count' => 'max']);
});

it('revokes unused codes only', function (): void {
    $unused = Invitation::factory()->create();
    $used = Invitation::factory()->used()->create();

    livewire(ListInvitations::class)
        ->assertActionHidden(TestAction::make(DeleteAction::class)->table($used))
        ->callAction(TestAction::make(DeleteAction::class)->table($unused));

    expect(Invitation::query()->whereKey($unused->id)->exists())->toBeFalse();
});
