<?php

declare(strict_types=1);

use App\Enums\Plan;
use App\Jobs\ProcessUrlSubmissionJob;
use App\Models\Submission;
use App\Models\UrlSubmission;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

it('requires authentication', function (): void {
    $this->post(route('url-submissions.store'), ['url' => 'https://example.com'])
        ->assertRedirectToRoute('login');
});

it('may submit a url', function (): void {
    Queue::fake();

    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->fromRoute('dashboard')
        ->post(route('url-submissions.store'), ['url' => 'https://example.com/article']);

    $urlSubmission = UrlSubmission::query()->sole();

    $response->assertRedirectToRoute('dashboard', ['link' => $urlSubmission->id]);

    expect($urlSubmission->user->is($user))->toBeTrue()
        ->and($urlSubmission->url)->toBe('https://example.com/article');

    Queue::assertPushed(ProcessUrlSubmissionJob::class);
});

it('validates the url', function (array $data): void {
    Queue::fake();

    $this->actingAs(User::factory()->create())
        ->fromRoute('dashboard')
        ->post(route('url-submissions.store'), $data)
        ->assertRedirectToRoute('dashboard')
        ->assertSessionHasErrors('url');

    Queue::assertNothingPushed();
})->with([
    'missing' => [[]],
    'invalid' => [['url' => 'not a url']],
    'wrong protocol' => [['url' => 'ftp://example.com/file']],
]);

it('limits the daily submissions', function (): void {
    Queue::fake();

    $user = User::factory()->create(['plan' => Plan::Standard]);

    Submission::factory()->for($user)->count(Plan::Standard->dailySubmissions() - 1)->create();
    UrlSubmission::factory()->for($user)->create();

    $this->actingAs($user)
        ->fromRoute('dashboard')
        ->post(route('url-submissions.store'), ['url' => 'https://example.com/article'])
        ->assertRedirectToRoute('dashboard')
        ->assertSessionHasErrors(['limit' => 'You have used all of your analyses for today.']);

    Queue::assertNothingPushed();
});
