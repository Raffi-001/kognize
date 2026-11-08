<?php

declare(strict_types=1);

use App\Enums\AnalysisType;
use App\Enums\SubmissionState;
use App\Models\AnalysisNote;
use App\Models\Submission;
use App\Models\SubmissionAnalysis;
use App\Models\User;

it('renders the analyze page with the composer', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();

    $this->actingAs($user);

    $page = visit(route('dashboard'));

    $page->assertSee('Analyze a text')
        ->assertDontSee('What we look for')
        ->assertSee('No analyses yet')
        ->assertSee('Fetch & analyze')
        ->click('Text')
        ->click('Try a sample text →')
        ->assertSeeIn('@word-count', '/ 30–500 words')
        ->assertNoJavaScriptErrors();
});

it('renders an analyzed submission and links passages to findings', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $submission = Submission::factory()->for($user)->analyzed()->create([
        'title' => 'Demo analysis',
        'body' => 'Մեր ազգի ներկան թշվառ է։ Ձեր վերա է միակ հույսը, և ուրիշ ճանապարհ չկա։',
    ]);
    $analysis = SubmissionAnalysis::factory()->for($submission)->create(['type' => AnalysisType::Fallacy]);
    AnalysisNote::factory()->for($analysis, 'analysis')->create([
        'annotation_id' => 'a1',
        'label' => 'Ձեր վերա է միակ հույսը',
        'aspect_label' => 'Կեղծ երկընտրանք (False Dilemma)',
        'strength' => 8,
    ]);
    $rhetoric = SubmissionAnalysis::factory()->for($submission)->create(['type' => AnalysisType::Rhetoric]);
    AnalysisNote::factory()->for($rhetoric, 'analysis')->create([
        'annotation_id' => 'r1',
        'label' => 'Մեր ազգի ներկան թշվառ է',
        'aspect_label' => 'Hyperbole',
        'strength' => 7,
    ]);

    $this->actingAs($user);

    $page = visit(route('submissions.show', $submission));

    $page->assertSee('Demo analysis')
        ->assertSee('False Dilemma')
        ->assertSee('Argument health')
        ->click('Ձեր վերա է միակ հույսը')
        ->assertAttribute('mark[data-type="fallacy"]', 'data-active', 'true')
        ->assertSee('Presenting a limited set of options as the only ones')
        ->script("document.dispatchEvent(new KeyboardEvent('keydown', { key: 'j' }))");

    $page
        ->assertAttribute('mark[data-type="rhetoric"]', 'data-active', 'true')
        ->assertNoJavaScriptErrors();
});

it('brings the active finding level with its passage when the margin is crowded', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $sentences = collect(range(1, 40))->map(fn (int $i): string => sprintf('Նախադասություն համար %d պարունակում է պնդում, որը պետք է ստուգել մանրամասն և քննադատաբար։', $i));
    $submission = Submission::factory()->for($user)->analyzed()->create(['body' => $sentences->implode(' ')]);
    $analysis = SubmissionAnalysis::factory()->for($submission)->create(['type' => AnalysisType::Fallacy]);

    foreach ($sentences->take(18) as $i => $sentence) {
        AnalysisNote::factory()->for($analysis, 'analysis')->create([
            'annotation_id' => 'a'.$i,
            'label' => mb_substr($sentence, 0, 24),
            'aspect_label' => 'Finding number '.($i + 1),
            'strength' => 8,
        ]);
    }

    $this->actingAs($user);

    $page = visit(route('submissions.show', $submission))->resize(1440, 900);

    $page->click('Նախադասություն համար 18');

    $page->wait(1)
        ->assertSee('Finding number 18')
        ->assertScript(<<<'JS'
            (() => {
                const mark = document.querySelector('mark[data-active="true"]').getBoundingClientRect();
                const item = document.querySelector('[data-test="finding"][data-active="true"]').getBoundingClientRect();

                return Math.abs(item.top - mark.top) < 40 && item.top >= 0 && item.top < window.innerHeight;
            })()
        JS)
        ->script('window.scrollBy(0, 150)');

    $page->wait(0.5)
        ->assertScript(<<<'JS'
            (() => {
                const mark = document.querySelector('mark[data-active="true"]').getBoundingClientRect();
                const item = document.querySelector('[data-test="finding"][data-active="true"]').getBoundingClientRect();

                return Math.abs(item.top - mark.top) < 40;
            })()
        JS)
        ->assertNoJavaScriptErrors();
});

it('renders the community feed and invitations pages', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    Submission::factory()->analyzed()->create(['title' => 'Someone else analysis']);

    $this->actingAs($user);

    visit(route('submissions.index', ['scope' => 'community']))
        ->assertSee('Community feed')
        ->assertSee('Someone else analysis')
        ->assertNoJavaScriptErrors();

    visit(route('invitations.index'))
        ->assertSee('Invite someone')
        ->assertSee('No invitations sent yet')
        ->assertNoJavaScriptErrors();
});

it('turns the composer into a progress box that links to the finished analysis', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $submission = Submission::factory()->for($user)->processing()->create(['title' => 'Ընթացքի մեջ']);

    $this->actingAs($user);

    $page = visit(route('dashboard', ['submission' => $submission->id]));

    $page->assertSee('Analyzing…')
        ->assertSee('Mapping the argument')
        ->assertSee('Watch it live')
        ->assertDontSee('Fetch & analyze');

    $submission->update(['state' => SubmissionState::Analyzed]);

    $page->wait(4)
        ->assertSee('Your analysis is ready')
        ->click('Open the analysis')
        ->assertPathIs('/submissions/'.$submission->id)
        ->assertNoJavaScriptErrors();
});

it('opens the finding of a tapped passage in a sheet on small screens', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $submission = Submission::factory()->for($user)->analyzed()->create([
        'body' => 'Մեր ազգի ներկան թշվառ է։ Ձեր վերա է միակ հույսը, և ուրիշ ճանապարհ չկա։',
    ]);
    $analysis = SubmissionAnalysis::factory()->for($submission)->create(['type' => AnalysisType::Fallacy]);
    AnalysisNote::factory()->for($analysis, 'analysis')->create([
        'label' => 'Ձեր վերա է միակ հույսը',
        'aspect_label' => 'Կեղծ երկընտրանք (False Dilemma)',
    ]);
    $rhetoric = SubmissionAnalysis::factory()->for($submission)->create(['type' => AnalysisType::Rhetoric]);
    AnalysisNote::factory()->for($rhetoric, 'analysis')->create([
        'label' => 'Մեր ազգի ներկան թշվառ է',
        'aspect_label' => 'Hyperbole',
    ]);

    $this->actingAs($user);

    $page = visit(route('submissions.show', $submission))->on()->mobile();

    $page->click('Ձեր վերա է միակ հույսը')
        ->assertVisible('@finding-sheet')
        ->assertSeeIn('@finding-sheet', 'What is a false dilemma?')
        ->assertSeeIn('@finding-sheet', '2 of 2')
        ->click('[aria-label="Previous finding"]')
        ->assertSeeIn('@finding-sheet', 'What is a hyperbole?')
        ->assertNoJavaScriptErrors();
});
