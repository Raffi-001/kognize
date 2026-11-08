<?php

declare(strict_types=1);

use App\Mail\NewsletterMail;
use App\Models\AnalysisNote;
use App\Models\Newsletter;
use App\Models\Poll;
use App\Models\Submission;
use App\Models\User;

it('lists the selected submissions, polls and open questions', function (): void {
    $newsletter = Newsletter::factory()->create(['subject' => 'This week', 'intro' => 'Here is what is new.']);
    $submission = Submission::factory()->analyzed()->create(['title' => 'Coffee [ad]']);
    $poll = Poll::factory()->published()->create(['question' => 'Should voting be compulsory?']);
    $question = AnalysisNote::factory()->question()->create(['aspect_label' => 'Who measured it?']);
    $unlabelled = AnalysisNote::factory()->question()->create(['aspect_label' => null, 'text' => 'Which baristas?']);

    $newsletter->submissions()->attach($submission);
    $newsletter->polls()->attach($poll);
    $newsletter->questions()->attach([$question->id, $unlabelled->id]);

    new NewsletterMail($newsletter, User::factory()->create())
        ->assertHasSubject('This week')
        ->assertSeeInHtml('Here is what is new.')
        ->assertSeeInOrderInHtml(['Submissions', 'Coffee [ad]', 'Polls', 'Should voting be compulsory?', 'Open Questions', 'Who measured it?', 'Which baristas?'])
        ->assertSeeInHtml(route('submissions.show', $submission), false)
        ->assertSeeInHtml(route('polls.index'), false)
        ->assertSeeInHtml(route('questions.show', $question), false);
});

it('leaves out categories with nothing selected', function (): void {
    $newsletter = Newsletter::factory()->create();
    $newsletter->polls()->attach(Poll::factory()->published()->create());

    new NewsletterMail($newsletter, User::factory()->create())
        ->assertSeeInHtml('Polls')
        ->assertDontSeeInHtml('Submissions')
        ->assertDontSeeInHtml('Open Questions');
});
