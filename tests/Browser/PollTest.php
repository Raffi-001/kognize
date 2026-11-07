<?php

declare(strict_types=1);

use App\Enums\AnalysisType;
use App\Enums\QuestionKind;
use App\Jobs\VerifyEvidenceJob;
use App\Models\AnalysisNote;
use App\Models\Poll;
use App\Models\Submission;
use App\Models\SubmissionAnalysis;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

it('lets readers answer a public question and only then shows results and the source', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $submission = Submission::factory()->analyzed()->create(['title' => 'Source analysis']);
    $analysis = SubmissionAnalysis::factory()->for($submission)->create(['type' => AnalysisType::Questions]);
    $question = AnalysisNote::factory()->question(QuestionKind::Value)->create(['submission_analysis_id' => $analysis->id]);
    Poll::factory()->published()->withOptions(['Համաձայն եմ', 'Համաձայն չեմ'])->create([
        'analysis_note_id' => $question->id,
        'question' => 'Արդյո՞ք սա ճիշտ է։',
    ]);

    $this->actingAs($user);

    $page = visit(route('polls.index'));

    $page->assertSee('Արդյո՞ք սա ճիշտ է։')
        ->assertDontSee('See the text this came from')
        ->click('Համաձայն եմ')
        ->fill('reason', 'Because of the reasons.')
        ->click('Answer')
        ->assertSee('1 answers')
        ->assertSee('100%')
        ->assertSee('See the text this came from')
        ->assertNoJavaScriptErrors();
});

it('lets readers add evidence to an open factual question', function (): void {
    Queue::fake();

    $user = User::factory()->withoutTwoFactor()->create();
    $submission = Submission::factory()->analyzed()->create();
    $analysis = SubmissionAnalysis::factory()->for($submission)->create(['type' => AnalysisType::Questions]);
    AnalysisNote::factory()->question()->create([
        'submission_analysis_id' => $analysis->id,
        'label' => 'Եթե դու համաձայն չես կառավարության հետ',
        'aspect_label' => 'Ի՞նչ փաստեր կան։',
    ]);

    $this->actingAs($user);

    $page = visit(route('submissions.show', $submission))->resize(1440, 900);

    $page->click('Եթե դու համաձայն չես կառավարության հետ')
        ->assertSee('Open for evidence')
        ->click('Add evidence')
        ->fill('url', 'https://example.am/report')
        ->fill('quote', 'Պաշտոնական զեկույցում նշված է հետևյալը։')
        ->click('Submit evidence')
        ->assertSee('Checking the source')
        ->assertNoJavaScriptErrors();

    Queue::assertPushed(VerifyEvidenceJob::class);
});
