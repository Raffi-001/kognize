<?php

declare(strict_types=1);

use App\Actions\CreateEvidence;
use App\Enums\EvidenceVerification;
use App\Enums\Stance;
use App\Jobs\VerifyEvidenceJob;
use App\Models\AnalysisNote;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

it('creates evidence and queues its verification', function (?string $explanation, ?string $expected): void {
    Queue::fake();

    $question = AnalysisNote::factory()->question()->create();
    $user = User::factory()->create();

    $evidence = resolve(CreateEvidence::class)->handle($question, $user, Stance::Refutes, 'https://example.com/report', '  Մեջբերում աղբյուրից  ', $explanation);

    expect($evidence->question->is($question))->toBeTrue()
        ->and($evidence->user->is($user))->toBeTrue()
        ->and($evidence->stance)->toBe(Stance::Refutes)
        ->and($evidence->url)->toBe('https://example.com/report')
        ->and($evidence->quote)->toBe('Մեջբերում աղբյուրից')
        ->and($evidence->explanation)->toBe($expected)
        ->and($evidence->refresh()->verification)->toBe(EvidenceVerification::Pending);

    Queue::assertPushed(VerifyEvidenceJob::class, fn (VerifyEvidenceJob $job): bool => $job->evidence->is($evidence));
})->with([
    'explanation' => ['  Բացատրություն  ', 'Բացատրություն'],
    'blank explanation' => [' ', null],
    'no explanation' => [null, null],
]);
