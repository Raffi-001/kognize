<?php

declare(strict_types=1);

use App\Enums\QuestionKind;
use App\Enums\QuestionStatus;
use App\Enums\Stance;
use App\Jobs\VerifyEvidenceJob;
use App\Models\AnalysisNote;
use App\Models\Evidence;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

beforeEach(function (): void {
    Queue::fake();
});

it('requires authentication', function (): void {
    $question = AnalysisNote::factory()->question()->create();

    $this->post(route('analysis-notes.evidence.store', $question), [])
        ->assertRedirectToRoute('login');
});

it('may add evidence to an open factual question', function (?string $explanation, ?string $expected): void {
    $user = User::factory()->create();
    $question = AnalysisNote::factory()->question()->create();
    $submission = $question->analysis->submission;

    $response = $this->actingAs($user)
        ->fromRoute('submissions.show', $submission)
        ->post(route('analysis-notes.evidence.store', $question), [
            'stance' => 'refutes',
            'url' => 'https://example.com/report',
            'quote' => 'Բյուջեի ծախսերն աճել են տասը տոկոսով',
            'explanation' => $explanation,
        ]);

    $response->assertRedirectToRoute('submissions.show', $submission)
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => __('Thanks. We are checking that the passage is on the page.')]);

    $evidence = Evidence::query()->sole();

    expect($evidence->question->is($question))->toBeTrue()
        ->and($evidence->user->is($user))->toBeTrue()
        ->and($evidence->stance)->toBe(Stance::Refutes)
        ->and($evidence->url)->toBe('https://example.com/report')
        ->and($evidence->quote)->toBe('Բյուջեի ծախսերն աճել են տասը տոկոսով')
        ->and($evidence->explanation)->toBe($expected);

    Queue::assertPushed(VerifyEvidenceJob::class);
})->with([
    'with an explanation' => ['Բացատրություն', 'Բացատրություն'],
    'without an explanation' => [null, null],
]);

it('validates the evidence', function (array $data, string $error): void {
    $question = AnalysisNote::factory()->question()->create();

    $this->actingAs(User::factory()->create())
        ->fromRoute('dashboard')
        ->post(route('analysis-notes.evidence.store', $question), [
            'stance' => 'supports',
            'url' => 'https://example.com/report',
            'quote' => 'Բյուջեի ծախսերն աճել են',
            ...$data,
        ])
        ->assertRedirectToRoute('dashboard')
        ->assertSessionHasErrors($error);

    expect(Evidence::query()->count())->toBe(0);

    Queue::assertNothingPushed();
})->with([
    'missing stance' => [['stance' => null], 'stance'],
    'unknown stance' => [['stance' => 'neutral'], 'stance'],
    'missing url' => [['url' => null], 'url'],
    'not a url' => [['url' => 'example'], 'url'],
    'not a web url' => [['url' => 'ftp://example.com/report'], 'url'],
    'long url' => [['url' => 'https://example.com/'.str_repeat('a', 2030)], 'url'],
    'missing quote' => [['quote' => null], 'quote'],
    'long quote' => [['quote' => str_repeat('a', 1001)], 'quote'],
    'long explanation' => [['explanation' => str_repeat('a', 1001)], 'explanation'],
]);

it('asks for the exact passage when the quote is too short', function (): void {
    $question = AnalysisNote::factory()->question()->create();

    $this->actingAs(User::factory()->create())
        ->fromRoute('dashboard')
        ->post(route('analysis-notes.evidence.store', $question), [
            'stance' => 'supports',
            'url' => 'https://example.com/report',
            'quote' => 'Կարճ',
        ])
        ->assertSessionHasErrors(['quote' => 'Copy the exact passage from the source that answers the question.']);
});

it('only accepts evidence for open factual questions', function (Closure $note): void {
    $this->actingAs(User::factory()->create())
        ->fromRoute('dashboard')
        ->post(route('analysis-notes.evidence.store', $note()), [
            'stance' => 'supports',
            'url' => 'https://example.com/report',
            'quote' => 'Բյուջեի ծախսերն աճել են',
        ])
        ->assertRedirectToRoute('dashboard')
        ->assertSessionHasErrors(['quote' => 'Evidence can only be added to open factual questions.']);

    expect(Evidence::query()->count())->toBe(0);
})->with([
    'finding' => [fn () => AnalysisNote::factory()->create()],
    'value question' => [fn () => AnalysisNote::factory()->question(QuestionKind::Value)->create()],
    'resolved question' => [fn () => AnalysisNote::factory()->question()->create(['status' => QuestionStatus::Resolved])],
]);
