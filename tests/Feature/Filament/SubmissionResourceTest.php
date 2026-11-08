<?php

declare(strict_types=1);

use App\Enums\AnalysisType;
use App\Enums\SubmissionKind;
use App\Enums\SubmissionState;
use App\Filament\Resources\Submissions\Pages\ListSubmissions;
use App\Filament\Resources\Submissions\Pages\ViewSubmission;
use App\Filament\Resources\Submissions\RelationManagers\NotesRelationManager;
use App\Models\AnalysisNote;
use App\Models\Report;
use App\Models\Submission;
use App\Models\SubmissionAnalysis;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\Bus;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->admin = User::factory()->admin()->create();

    $this->actingAs($this->admin);
});

it('lists submissions and filters them', function (): void {
    $failed = Submission::factory()->failed()->create(['source' => 'https://news.example.am/article']);
    $analyzed = Submission::factory()->analyzed()->create(['kind' => SubmissionKind::Argument, 'title' => 'Սուրճի գովազդ']);
    AnalysisNote::factory()->for(SubmissionAnalysis::factory()->for($analyzed), 'analysis')->create();
    Report::factory()->create(['reportable_type' => 'submission', 'reportable_id' => $analyzed->id]);

    livewire(ListSubmissions::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$failed, $analyzed])
        ->assertSee('news.example.am')
        ->assertTableColumnStateSet('notes_count', 1, $analyzed)
        ->assertTableColumnStateSet('reports_count', 1, $analyzed)
        ->assertTableColumnStateSet('reports_count', 0, $failed)
        ->filterTable('state', SubmissionState::Failed->value)
        ->assertCanSeeTableRecords([$failed])
        ->assertCanNotSeeTableRecords([$analyzed])
        ->resetTableFilters()
        ->filterTable('source', true)
        ->assertCanSeeTableRecords([$failed])
        ->assertCanNotSeeTableRecords([$analyzed])
        ->resetTableFilters()
        ->searchTable('Սուրճի')
        ->assertCanSeeTableRecords([$analyzed])
        ->assertCanNotSeeTableRecords([$failed]);
});

it('offers a retry only for failed submissions', function (): void {
    Bus::fake();

    $failed = Submission::factory()->failed()->create();
    $analyzed = Submission::factory()->analyzed()->create();

    livewire(ListSubmissions::class)
        ->assertActionHidden(TestAction::make('retry')->table($analyzed))
        ->callAction(TestAction::make('retry')->table($failed))
        ->assertNotified('Analysis queued again');

    expect($failed->refresh()->state)->toBe(SubmissionState::Pending);
});

it('shows a submission with its analyses and findings', function (): void {
    $submission = Submission::factory()->analyzed()->create(['body' => 'Եթե մեր սուրճը քեզ դուր չի գալիս']);
    $analysis = SubmissionAnalysis::factory()->for($submission)->create(['type' => AnalysisType::Rhetoric]);
    $note = AnalysisNote::factory()->for($analysis, 'analysis')->create(['aspect_label' => 'Manufactured urgency']);
    $otherNote = AnalysisNote::factory()->create();

    livewire(ViewSubmission::class, ['record' => $submission->getRouteKey()])
        ->assertOk()
        ->assertSee('Եթե մեր սուրճը քեզ դուր չի գալիս')
        ->assertSee('Rhetoric')
        ->assertSee($submission->user->name)
        ->assertActionVisible('open')
        ->assertActionHidden('retry');

    livewire(NotesRelationManager::class, [
        'ownerRecord' => $submission,
        'pageClass' => ViewSubmission::class,
    ])
        ->assertOk()
        ->assertCanSeeTableRecords([$note])
        ->assertCanNotSeeTableRecords([$otherNote])
        ->assertTableColumnStateSet('type', 'Rhetoric', $note)
        ->filterTable('type', AnalysisType::Fallacy->value)
        ->assertCanNotSeeTableRecords([$note])
        ->filterTable('type', AnalysisType::Rhetoric->value)
        ->assertCanSeeTableRecords([$note]);
});

it('deletes a submission from its page with its reports', function (): void {
    $submission = Submission::factory()->create();
    $report = Report::factory()->create(['reportable_type' => 'submission', 'reportable_id' => $submission->id]);

    livewire(ViewSubmission::class, ['record' => $submission->getRouteKey()])
        ->callAction(DeleteAction::class);

    expect(Submission::query()->whereKey($submission->id)->exists())->toBeFalse()
        ->and($report->fresh())->toBeNull();
});

it('deletes submissions in bulk', function (): void {
    $submissions = Submission::factory()->count(2)->create();

    livewire(ListSubmissions::class)
        ->selectTableRecords($submissions->pluck('id')->all())
        ->callAction(TestAction::make(DeleteBulkAction::class)->table()->bulk());

    expect(Submission::query()->count())->toBe(0);
});
