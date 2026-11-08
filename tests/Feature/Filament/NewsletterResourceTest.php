<?php

declare(strict_types=1);

use App\Filament\Resources\Newsletters\Pages\CreateNewsletter;
use App\Filament\Resources\Newsletters\Pages\EditNewsletter;
use App\Filament\Resources\Newsletters\Pages\ListNewsletters;
use App\Models\AnalysisNote;
use App\Models\Newsletter;
use App\Models\Poll;
use App\Models\Submission;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\Testing\TestAction;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->admin = User::factory()->admin()->create();

    $this->actingAs($this->admin);
});

it('lists newsletters with what they contain', function (): void {
    $newsletter = Newsletter::factory()->create();
    $newsletter->polls()->attach(Poll::factory()->published()->count(2)->create());
    $newsletter->recipients()->attach($this->admin);

    livewire(ListNewsletters::class)
        ->assertOk()
        ->assertSee('Send newsletter')
        ->assertCanSeeTableRecords([$newsletter])
        ->assertTableColumnStateSet('polls_count', 2, $newsletter)
        ->assertTableColumnStateSet('recipients_count', 1, $newsletter);
});

it('creates a newsletter from analyses, published polls and open questions', function (): void {
    $submission = Submission::factory()->analyzed()->create();
    $poll = Poll::factory()->published()->create();
    $question = AnalysisNote::factory()->question()->create();

    livewire(CreateNewsletter::class)
        ->fillForm([
            'subject' => 'This week',
            'intro' => 'Hello',
            'submissions' => [$submission->id],
            'polls' => [$poll->id],
            'questions' => [$question->id],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $newsletter = Newsletter::query()->sole();

    expect($newsletter->subject)->toBe('This week')
        ->and($newsletter->intro)->toBe('Hello')
        ->and($newsletter->submissions->sole()->is($submission))->toBeTrue()
        ->and($newsletter->polls->sole()->is($poll))->toBeTrue()
        ->and($newsletter->questions->sole()->is($question))->toBeTrue();
});

it('offers only analyzed submissions, published polls and open factual questions', function (): void {
    $analyzed = Submission::factory()->analyzed()->create(['title' => null]);
    Submission::factory()->create();
    Poll::factory()->create();
    $published = Poll::factory()->published()->create();
    $open = AnalysisNote::factory()->question()->create(['aspect_label' => null, 'text' => 'Ո՞վ է չափել']);

    livewire(CreateNewsletter::class)
        ->assertFormFieldExists('submissions', fn ($field): bool => $field->getOptions() === [$analyzed->id => $analyzed->displayTitle()])
        ->assertFormFieldExists('polls', fn ($field): bool => array_keys($field->getOptions()) === [$published->id])
        ->assertFormFieldExists('questions', fn ($field): bool => $field->getOptions() === [$open->id => 'Ո՞վ է չափել']);
});

it('requires a subject', function (): void {
    livewire(CreateNewsletter::class)
        ->fillForm(['subject' => null])
        ->call('create')
        ->assertHasFormErrors(['subject' => 'required']);
});

it('edits a newsletter and previews it', function (): void {
    $newsletter = Newsletter::factory()->create(['subject' => 'Old']);
    $newsletter->polls()->attach(Poll::factory()->published()->create(['question' => 'Is it fair?']));

    livewire(EditNewsletter::class, ['record' => $newsletter->id])
        ->assertOk()
        ->fillForm(['subject' => 'New', 'polls' => []])
        ->call('save')
        ->assertHasNoFormErrors()
        ->mountAction('preview')
        ->assertMountedActionModalSeeHtml('title="Newsletter preview"')
        ->assertMountedActionModalSee('New');

    expect($newsletter->refresh()->subject)->toBe('New')
        ->and($newsletter->polls)->toBeEmpty();
});

it('previews and deletes from the table', function (): void {
    $newsletter = Newsletter::factory()->create(['subject' => 'Weekly']);

    livewire(ListNewsletters::class)
        ->mountAction(TestAction::make('preview')->table($newsletter))
        ->assertMountedActionModalSeeHtml('title="Newsletter preview"');

    livewire(ListNewsletters::class)
        ->callAction(TestAction::make(DeleteAction::class)->table($newsletter));

    expect(Newsletter::query()->count())->toBe(0);
});
