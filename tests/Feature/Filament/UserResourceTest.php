<?php

declare(strict_types=1);

use App\Enums\Plan;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Pages\ViewUser;
use App\Filament\Resources\Users\RelationManagers\RefereesRelationManager;
use App\Filament\Resources\Users\UserResource;
use App\Mail\NewsletterMail;
use App\Models\Invitation;
use App\Models\Newsletter;
use App\Models\Submission;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\Mail;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->admin = User::factory()->admin()->create();

    $this->actingAs($this->admin);
});

it('lists users with their referrer and filters them', function (): void {
    $referee = User::factory()->unverified()->create(['referrer_id' => $this->admin->id, 'plan' => Plan::Pro]);

    livewire(ListUsers::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$this->admin, $referee])
        ->assertTableColumnStateSet('referrer.name', $this->admin->name, $referee)
        ->assertTableColumnStateSet('verified', false, $referee)
        ->assertTableColumnStateSet('verified', true, $this->admin)
        ->assertTableColumnStateSet('referees_count', 1, $this->admin)
        ->filterTable('referrer_id', true)
        ->assertCanSeeTableRecords([$referee])
        ->assertCanNotSeeTableRecords([$this->admin])
        ->resetTableFilters()
        ->filterTable('plan', Plan::Pro->value)
        ->assertCanSeeTableRecords([$referee])
        ->assertCanNotSeeTableRecords([$this->admin])
        ->resetTableFilters()
        ->searchTable($referee->email)
        ->assertCanSeeTableRecords([$referee])
        ->assertCanNotSeeTableRecords([$this->admin]);
});

it('shows a user with referrals and activity', function (): void {
    $member = User::factory()->create(['referrer_id' => $this->admin->id]);
    User::factory()->create(['referrer_id' => $member->id]);
    Invitation::factory()->for($member, 'inviter')->create();
    Submission::factory()->for($member)->create();

    livewire(ViewUser::class, ['record' => $member->getRouteKey()])
        ->assertOk()
        ->assertSee($member->email)
        ->assertSee($this->admin->name)
        ->assertSee('People they invited')
        ->assertSee('1 of '.$member->plan->dailySubmissions());

    livewire(ViewUser::class, ['record' => $this->admin->getRouteKey()])
        ->assertOk()
        ->assertSee('Nobody (first users and admins)');
});

it('lists the people a user invited', function (): void {
    $referee = User::factory()->create(['referrer_id' => $this->admin->id]);
    $stranger = User::factory()->create();

    livewire(RefereesRelationManager::class, [
        'ownerRecord' => $this->admin,
        'pageClass' => ViewUser::class,
    ])
        ->assertOk()
        ->assertCanSeeTableRecords([$referee])
        ->assertCanNotSeeTableRecords([$stranger])
        ->assertSee(UserResource::getUrl('view', ['record' => $referee]), escape: false);
});

it('edits a user', function (): void {
    $member = User::factory()->create();

    livewire(EditUser::class, ['record' => $member->getRouteKey()])
        ->assertOk()
        ->assertFormFieldEnabled('is_admin')
        ->assertActionVisible(DeleteAction::class)
        ->fillForm([
            'name' => 'Renamed',
            'plan' => Plan::Pro->value,
            'is_admin' => true,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($member->refresh())
        ->name->toBe('Renamed')
        ->plan->toBe(Plan::Pro)
        ->is_admin->toBeTrue();
});

it('keeps admins from demoting or deleting themselves', function (): void {
    livewire(EditUser::class, ['record' => $this->admin->getRouteKey()])
        ->assertFormFieldDisabled('is_admin')
        ->assertActionHidden(DeleteAction::class);
});

it('deletes a user', function (): void {
    $member = User::factory()->create();

    livewire(EditUser::class, ['record' => $member->getRouteKey()])
        ->callAction(DeleteAction::class);

    expect(User::query()->whereKey($member->id)->exists())->toBeFalse();
});

it('sends a newsletter to the selected users', function (): void {
    Mail::fake();

    $newsletter = Newsletter::factory()->create(['subject' => 'Weekly']);
    $users = User::factory()->count(2)->create();

    livewire(ListUsers::class)
        ->selectTableRecords($users)
        ->callAction(TestAction::make('sendNewsletter')->table()->bulk(), ['newsletter_id' => $newsletter->id])
        ->assertHasNoFormErrors()
        ->assertNotified('Sending “Weekly” to 2 users');

    Mail::assertQueued(NewsletterMail::class, 2);
    Mail::assertNotQueued(NewsletterMail::class, fn (NewsletterMail $mail): bool => $mail->hasTo($this->admin->email));

    expect($newsletter->recipients->pluck('id')->sort()->values()->all())->toBe($users->pluck('id')->sort()->values()->all());
});

it('requires a newsletter to send', function (): void {
    livewire(ListUsers::class)
        ->selectTableRecords([$this->admin])
        ->callAction(TestAction::make('sendNewsletter')->table()->bulk(), ['newsletter_id' => null])
        ->assertHasFormErrors(['newsletter_id' => 'required']);
});
