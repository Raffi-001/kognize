<?php

declare(strict_types=1);

use App\Enums\EvidenceAssessment;
use App\Enums\EvidenceVerification;
use App\Enums\QuestionKind;
use App\Enums\QuestionStatus;
use App\Enums\Verdict;
use App\Filament\Resources\AnalysisNotes\AnalysisNoteResource;
use App\Filament\Resources\AnalysisNotes\Pages\ListAnalysisNotes;
use App\Filament\Resources\AnalysisNotes\Pages\ViewAnalysisNote;
use App\Filament\Resources\AnalysisNotes\RelationManagers\EvidenceRelationManager;
use App\Jobs\VerifyEvidenceJob;
use App\Models\AnalysisNote;
use App\Models\Evidence;
use App\Models\Poll;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\Queue;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->admin = User::factory()->admin()->create();

    $this->actingAs($this->admin);
});

it('lists the open questions only by default', function (): void {
    $open = AnalysisNote::factory()->question()->create();
    $value = AnalysisNote::factory()->question(QuestionKind::Value)->create();
    $resolved = AnalysisNote::factory()->question()->create(['status' => QuestionStatus::Resolved, 'verdict' => Verdict::Refuted]);
    $finding = AnalysisNote::factory()->create();
    Evidence::factory()->for($open, 'question')->verified(EvidenceAssessment::Supports)->create();

    livewire(ListAnalysisNotes::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$open, $value])
        ->assertCanNotSeeTableRecords([$resolved, $finding])
        ->assertTableColumnStateSet('suggested_verdict', Verdict::Supported, $open)
        ->assertTableColumnStateSet('evidence_count', 1, $open)
        ->filterTable('status', QuestionStatus::Resolved->value)
        ->assertCanSeeTableRecords([$resolved])
        ->assertTableColumnStateSet('suggested_verdict', Verdict::Refuted, $resolved)
        ->filterTable('status', null)
        ->filterTable('question_kind', QuestionKind::Value->value)
        ->assertCanSeeTableRecords([$value])
        ->assertCanNotSeeTableRecords([$open, $resolved, $finding]);

    expect(AnalysisNoteResource::canCreate())->toBeFalse();
});

it('resolves a factual question from the table with the suggested verdict', function (): void {
    $question = AnalysisNote::factory()->question()->create();
    Evidence::factory()->for($question, 'question')->verified(EvidenceAssessment::Refutes)->create();

    livewire(ListAnalysisNotes::class)
        ->mountAction(TestAction::make('resolve')->table($question))
        ->assertSchemaStateSet(['verdict' => Verdict::Refuted])
        ->setActionData(['resolution' => 'Աղբյուրը հերքում է։'])
        ->callMountedAction()
        ->assertHasNoFormErrors()
        ->assertNotified('Question resolved');

    expect($question->refresh()->status)->toBe(QuestionStatus::Resolved)
        ->and($question->verdict)->toBe(Verdict::Refuted)
        ->and($question->resolution)->toBe('Աղբյուրը հերքում է։')
        ->and($question->resolved_by)->toBe($this->admin->id);
});

it('requires a verdict and an explanation to resolve a question', function (): void {
    $question = AnalysisNote::factory()->question()->create();

    livewire(ListAnalysisNotes::class)
        ->callAction(TestAction::make('resolve')->table($question), ['verdict' => null, 'resolution' => null])
        ->assertHasFormErrors(['verdict' => 'required', 'resolution' => 'required']);

    expect($question->refresh()->status)->toBe(QuestionStatus::Open);
});

it('shows a factual question with its resolution actions', function (): void {
    $question = AnalysisNote::factory()->question()->create(['label' => 'Մեջբերված հատված']);

    livewire(ViewAnalysisNote::class, ['record' => $question->id])
        ->assertOk()
        ->assertSee('Մեջբերված հատված')
        ->assertSee('No verified evidence yet')
        ->assertSee(route('submissions.show', $question->analysis->submission_id))
        ->assertDontSee('Public poll')
        ->assertActionVisible('resolve')
        ->assertActionHidden('reopen')
        ->callAction('resolve', ['verdict' => Verdict::Supported, 'resolution' => 'Հաստատված է։'])
        ->assertHasNoFormErrors()
        ->assertNotified('Question resolved');

    expect($question->refresh()->verdict)->toBe(Verdict::Supported);
});

it('reopens a resolved question', function (): void {
    $question = AnalysisNote::factory()->question()->create([
        'status' => QuestionStatus::Resolved,
        'verdict' => Verdict::Contested,
        'resolution' => 'Վիճելի է։',
        'resolved_by' => $this->admin->id,
        'resolved_at' => now(),
    ]);

    livewire(ViewAnalysisNote::class, ['record' => $question->id])
        ->assertOk()
        ->assertSee('Վիճելի է։')
        ->assertSee($this->admin->name)
        ->assertActionHidden('resolve')
        ->assertActionVisible('reopen')
        ->callAction('reopen')
        ->assertNotified('Question reopened');

    expect($question->refresh()->status)->toBe(QuestionStatus::Open)
        ->and($question->verdict)->toBeNull();
});

it('shows the poll of a value question', function (bool $hasPoll): void {
    $question = AnalysisNote::factory()->question(QuestionKind::Value)->create();

    if ($hasPoll) {
        Poll::factory()->create(['analysis_note_id' => $question->id, 'question' => 'Հանրային հարց']);
    }

    $component = livewire(ViewAnalysisNote::class, ['record' => $question->id])
        ->assertOk()
        ->assertSee('Public poll')
        ->assertActionHidden('resolve');

    $hasPoll
        ? $component->assertSee('Հանրային հարց')
        : $component->assertSee('No poll drafted');
})->with([
    'with a poll' => [true],
    'without a poll' => [false],
]);

it('lists the evidence of a question', function (): void {
    $question = AnalysisNote::factory()->question()->create();
    $verified = Evidence::factory()->for($question, 'question')->verified()->create(['page_title' => 'Զեկույց']);
    $pending = Evidence::factory()->for($question, 'question')->create(['url' => 'https://example.com/page', 'explanation' => 'Բացատրություն']);
    $other = Evidence::factory()->create();

    livewire(EvidenceRelationManager::class, ['ownerRecord' => $question, 'pageClass' => ViewAnalysisNote::class])
        ->assertOk()
        ->assertCanSeeTableRecords([$verified, $pending])
        ->assertCanNotSeeTableRecords([$other])
        ->assertTableColumnStateSet('url', 'Զեկույց', $verified)
        ->assertTableColumnStateSet('url', 'example.com', $pending)
        ->assertSee('Բացատրություն')
        ->filterTable('verification', EvidenceVerification::Verified->value)
        ->assertCanSeeTableRecords([$verified])
        ->assertCanNotSeeTableRecords([$pending]);
});

it('checks evidence again', function (): void {
    Queue::fake();

    $question = AnalysisNote::factory()->question()->create();
    $evidence = Evidence::factory()->for($question, 'question')->create(['verification' => EvidenceVerification::Unreachable]);

    livewire(EvidenceRelationManager::class, ['ownerRecord' => $question, 'pageClass' => ViewAnalysisNote::class])
        ->callAction(TestAction::make('reverify')->table($evidence))
        ->assertNotified('The source will be checked again');

    expect($evidence->refresh()->verification)->toBe(EvidenceVerification::Pending);

    Queue::assertPushed(VerifyEvidenceJob::class, fn (VerifyEvidenceJob $job): bool => $job->evidence->is($evidence));
});

it('deletes evidence', function (): void {
    $question = AnalysisNote::factory()->question()->create();
    $evidence = Evidence::factory()->for($question, 'question')->create();

    livewire(EvidenceRelationManager::class, ['ownerRecord' => $question, 'pageClass' => ViewAnalysisNote::class])
        ->callAction(TestAction::make(DeleteAction::class)->table($evidence));

    expect(Evidence::query()->count())->toBe(0);
});
