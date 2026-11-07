<?php

declare(strict_types=1);

use App\Enums\PollStatus;
use App\Enums\QuestionKind;
use App\Filament\Resources\Polls\Pages\CreatePoll;
use App\Filament\Resources\Polls\Pages\EditPoll;
use App\Filament\Resources\Polls\Pages\ListPolls;
use App\Filament\Resources\Polls\Pages\ViewPoll;
use App\Filament\Resources\Polls\RelationManagers\VotesRelationManager;
use App\Models\AnalysisNote;
use App\Models\Poll;
use App\Models\PollVote;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\Testing\TestAction;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->admin = User::factory()->admin()->create();

    $this->actingAs($this->admin);
});

it('lists the polls', function (): void {
    $polls = Poll::factory()->count(2)->create();
    $published = Poll::factory()->published()->create();
    $mirror = Poll::factory()->create(['mirror_of_id' => $published->id]);

    livewire(ListPolls::class)
        ->assertOk()
        ->assertCanSeeTableRecords([...$polls, $published, $mirror])
        ->assertTableColumnStateSet('is_mirror', true, $mirror)
        ->assertTableColumnStateSet('is_mirror', false, $published)
        ->filterTable('status', PollStatus::Published->value)
        ->assertCanSeeTableRecords([$published])
        ->assertCanNotSeeTableRecords([...$polls, $mirror]);
});

it('shows the actions that fit the status of a poll', function (Closure $poll, ?string $publishLabel, bool $canClose, bool $canChange): void {
    $poll = $poll();

    $component = livewire(ListPolls::class);

    $publishLabel === null
        ? $component->assertActionHidden(TestAction::make('publish')->table($poll))
        : $component->assertActionVisible(TestAction::make('publish')->table($poll))
            ->assertActionHasLabel(TestAction::make('publish')->table($poll), $publishLabel);

    $canClose
        ? $component->assertActionVisible(TestAction::make('close')->table($poll))
        : $component->assertActionHidden(TestAction::make('close')->table($poll));

    $canChange
        ? $component->assertActionVisible(TestAction::make(EditAction::class)->table($poll))
            ->assertActionVisible(TestAction::make(DeleteAction::class)->table($poll))
        : $component->assertActionHidden(TestAction::make(EditAction::class)->table($poll))
            ->assertActionHidden(TestAction::make(DeleteAction::class)->table($poll));
})->with([
    'draft' => [fn () => Poll::factory()->create(), 'Approve & publish', false, true],
    'published' => [fn () => Poll::factory()->published()->create(), null, true, false],
    'closed' => [fn () => Poll::factory()->closed()->create(), 'Reopen', false, false],
]);

it('publishes a poll from the table', function (): void {
    $poll = Poll::factory()->create();

    livewire(ListPolls::class)
        ->callAction(TestAction::make('publish')->table($poll))
        ->assertNotified('Poll published');

    expect($poll->refresh()->status)->toBe(PollStatus::Published)
        ->and($poll->approved_by)->toBe($this->admin->id);
});

it('closes a poll from the table', function (): void {
    $poll = Poll::factory()->published()->create();

    livewire(ListPolls::class)
        ->callAction(TestAction::make('close')->table($poll))
        ->assertNotified('Poll closed');

    expect($poll->refresh()->status)->toBe(PollStatus::Closed);
});

it('deletes a draft from the table', function (): void {
    $poll = Poll::factory()->create();

    livewire(ListPolls::class)
        ->callAction(TestAction::make(DeleteAction::class)->table($poll));

    expect(Poll::query()->count())->toBe(0);
});

it('creates a poll with its options', function (): void {
    livewire(CreatePoll::class)
        ->fillForm([
            'question' => 'Հարց',
            'options' => [['label' => 'Այո'], ['label' => 'Ոչ']],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $poll = Poll::query()->sole();

    expect($poll->question)->toBe('Հարց')
        ->and($poll->status)->toBe(PollStatus::Draft)
        ->and($poll->options->pluck('label')->all())->toBe(['Այո', 'Ոչ']);
});

it('validates the poll', function (array $data, array $errors): void {
    livewire(CreatePoll::class)
        ->fillForm($data)
        ->call('create')
        ->assertHasFormErrors($errors);

    expect(Poll::query()->count())->toBe(0);
})->with([
    'missing question' => [['question' => null, 'options' => [['label' => 'Այո'], ['label' => 'Ոչ']]], ['question' => 'required']],
    'missing option label' => [['question' => 'Հարց', 'options' => [['label' => 'Այո'], ['label' => null]]], ['options.1.label' => 'required']],
    'one option' => [['question' => 'Հարց', 'options' => [['label' => 'Այո']]], ['options']],
    'too many options' => [['question' => 'Հարց', 'options' => array_map(fn (int $number): array => ['label' => (string) $number], range(1, 6))], ['options']],
]);

it('edits a draft', function (): void {
    $poll = Poll::factory()->withOptions(['Այո', 'Ոչ'])->create();

    livewire(EditPoll::class, ['record' => $poll->id])
        ->assertOk()
        ->fillForm(['question' => 'Նոր հարց'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($poll->refresh()->question)->toBe('Նոր հարց')
        ->and($poll->options->pluck('label')->all())->toBe(['Այո', 'Ոչ']);
});

it('does not edit published polls', function (): void {
    $poll = Poll::factory()->published()->create();

    $this->get(EditPoll::getUrl(['record' => $poll]))
        ->assertForbidden();
});

it('shows a poll with its results and where it came from', function (): void {
    $question = AnalysisNote::factory()->question(QuestionKind::Value)->create(['aspect_label' => 'Բաց հարց']);
    $poll = Poll::factory()->published()->withOptions(['Այո', 'Ոչ'])->create(['analysis_note_id' => $question->id]);
    Poll::factory()->create(['mirror_of_id' => $poll->id, 'question' => 'Հայելային հարց']);
    PollVote::factory()->count(3)->create(['poll_option_id' => $poll->options[0]->id]);
    PollVote::factory()->create(['poll_option_id' => $poll->options[1]->id]);

    livewire(ViewPoll::class, ['record' => $poll->id])
        ->assertOk()
        ->assertSee('Բաց հարց')
        ->assertSee('Հայելային հարց')
        ->assertSee('3 answers (75%)')
        ->assertSee('1 answers (25%)')
        ->assertActionHidden('publish')
        ->assertActionVisible('close')
        ->assertActionHidden(EditAction::class)
        ->callAction('close')
        ->assertNotified('Poll closed');

    expect($poll->refresh()->status)->toBe(PollStatus::Closed);
});

it('shows a poll created by an admin', function (): void {
    $mirrorOf = Poll::factory()->create(['question' => 'Բնօրինակ']);
    $poll = Poll::factory()->withOptions()->create(['mirror_of_id' => $mirrorOf->id]);

    livewire(ViewPoll::class, ['record' => $poll->id])
        ->assertOk()
        ->assertSee('Created by an admin')
        ->assertSee('Բնօրինակ')
        ->assertSee('0 answers (0%)')
        ->assertActionVisible(EditAction::class)
        ->callAction('publish')
        ->assertNotified('Poll published');

    expect($poll->refresh()->status)->toBe(PollStatus::Published);
});

it('lists the answers of a poll read-only', function (): void {
    $poll = Poll::factory()->published()->withOptions(['Այո', 'Ոչ'])->create();
    $yes = PollVote::factory()->create(['poll_option_id' => $poll->options[0]->id, 'reason' => 'Որովհետև']);
    $no = PollVote::factory()->create(['poll_option_id' => $poll->options[1]->id]);
    PollVote::factory()->create();

    livewire(VotesRelationManager::class, ['ownerRecord' => $poll, 'pageClass' => ViewPoll::class])
        ->assertOk()
        ->assertCanSeeTableRecords([$yes, $no])
        ->assertCountTableRecords(2)
        ->assertSee('Որովհետև')
        ->assertSee('No reason given')
        ->filterTable('poll_option_id', $poll->options[0]->id)
        ->assertCanSeeTableRecords([$yes])
        ->assertCanNotSeeTableRecords([$no]);

    expect(new VotesRelationManager()->isReadOnly())->toBeTrue();
});
