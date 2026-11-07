<?php

declare(strict_types=1);

use App\Enums\AnalysisType;
use App\Models\AnalysisNote;
use App\Models\Submission;
use App\Models\SubmissionAnalysis;
use App\Models\User;

it('lists unresolved issues and opens the page of each one', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $submission = Submission::factory()->analyzed()->create(['title' => 'Աղբյուր հոդված']);
    $analysis = SubmissionAnalysis::factory()->for($submission)->create(['type' => AnalysisType::Questions]);
    AnalysisNote::factory()->question()->create([
        'submission_analysis_id' => $analysis->id,
        'label' => 'Եթե դու համաձայն չես կառավարության հետ',
        'aspect_label' => 'Ի՞նչ փաստեր կան։',
    ]);

    $this->actingAs($user);

    $page = visit(route('dashboard'))->resize(1440, 900);

    $page->assertSee('Workspace')
        ->assertDontSee('History')
        ->click('Unresolved issues')
        ->assertSee('Ի՞նչ փաստեր կան։')
        ->assertSee('Աղբյուր հոդված')
        ->click('Ի՞նչ փաստեր կան։')
        ->assertSee('Factual question')
        ->assertSee('Եթե դու համաձայն չես կառավարության հետ')
        ->assertSee('Open for evidence')
        ->assertSee('Discussion')
        ->assertNoJavaScriptErrors()
        ->screenshot(filename: 'question-show');
});
