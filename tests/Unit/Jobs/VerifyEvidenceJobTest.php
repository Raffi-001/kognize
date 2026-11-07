<?php

declare(strict_types=1);

use App\Enums\EvidenceVerification;
use App\Jobs\VerifyEvidenceJob;
use App\Models\Evidence;
use Illuminate\Support\Facades\Http;

it('verifies the evidence', function (): void {
    Http::fake(['https://example.com/report' => Http::response('', 404)]);

    $evidence = Evidence::factory()->create(['url' => 'https://example.com/report']);

    dispatch_sync(new VerifyEvidenceJob($evidence));

    expect($evidence->refresh()->verification)->toBe(EvidenceVerification::Unreachable);
});

it('marks the evidence as unreachable when the job fails', function (): void {
    $evidence = Evidence::factory()->create();

    new VerifyEvidenceJob($evidence)->failed();

    expect($evidence->refresh()->verification)->toBe(EvidenceVerification::Unreachable);
});
