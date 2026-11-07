<?php

declare(strict_types=1);

use App\Enums\Plan;
use App\Enums\QuestionKind;
use App\Enums\QuestionStatus;
use App\Enums\SubmissionState;
use App\Http\Middleware\HandleInertiaRequests;
use App\Models\AnalysisNote;
use App\Models\Invitation;
use App\Models\Poll;
use App\Models\PollVote;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Http\Request;

it('shares app name from config', function (): void {
    $middleware = new HandleInertiaRequests();

    $request = Request::create('/', 'GET');

    $shared = $middleware->share($request);

    expect($shared)->toHaveKey('name')
        ->and($shared['name'])->toBe(config('app.name'));
});

it('shares null user when guest', function (): void {
    $middleware = new HandleInertiaRequests();

    $request = Request::create('/', 'GET');

    $shared = $middleware->share($request);

    expect($shared)->toHaveKey('auth')
        ->and($shared['auth'])->toHaveKey('user')
        ->and($shared['auth']['user'])->toBeNull();
});

it('shares authenticated user data', function (): void {
    $user = User::factory()->create([
        'name' => 'Test User',
        'email' => 'test@example.com',
    ]);

    $middleware = new HandleInertiaRequests();

    $request = Request::create('/', 'GET');
    $request->setUserResolver(fn () => $user);

    $shared = $middleware->share($request);

    expect($shared['auth']['user'])->not->toBeNull()
        ->and($shared['auth']['user']->id)->toBe($user->id)
        ->and($shared['auth']['user']->name)->toBe('Test User')
        ->and($shared['auth']['user']->email)->toBe('test@example.com');
});

it('defaults sidebarOpen to true when no cookie', function (): void {
    $middleware = new HandleInertiaRequests();

    $request = Request::create('/', 'GET');

    $shared = $middleware->share($request);

    expect($shared)->toHaveKey('sidebarOpen')
        ->and($shared['sidebarOpen'])->toBeTrue();
});

it('sets sidebarOpen to true when cookie is true', function (): void {
    $middleware = new HandleInertiaRequests();

    $request = Request::create('/', 'GET');
    $request->cookies->set('sidebar_state', 'true');

    $shared = $middleware->share($request);

    expect($shared['sidebarOpen'])->toBeTrue();
});

it('sets sidebarOpen to false when cookie is false', function (): void {
    $middleware = new HandleInertiaRequests();

    $request = Request::create('/', 'GET');
    $request->cookies->set('sidebar_state', 'false');

    $shared = $middleware->share($request);

    expect($shared['sidebarOpen'])->toBeFalse();
});

it('includes parent shared data', function (): void {
    $middleware = new HandleInertiaRequests();

    $request = Request::create('/', 'GET');

    $shared = $middleware->share($request);

    // Parent Inertia middleware shares 'errors' by default
    expect($shared)->toHaveKey('errors');
});

it('shares null usage when guest', function (): void {
    $middleware = new HandleInertiaRequests();

    $shared = $middleware->share(Request::create('/', 'GET'));

    expect($shared['auth']['usage'])->toBeNull();
});

it('shares the usage of the authenticated user', function (): void {
    $user = User::factory()->create(['plan' => Plan::Pro]);

    Submission::factory()->for($user)->count(2)->create();
    Invitation::factory()->for($user, 'inviter')->create();

    $middleware = new HandleInertiaRequests();

    $request = Request::create('/', 'GET');
    $request->setUserResolver(fn () => $user);

    $usage = $middleware->share($request)['auth']['usage'];

    expect($usage)->toBeInstanceOf(Closure::class)
        ->and($usage())->toBe([
            'plan' => Plan::Pro,
            'plan_label' => 'Pro',
            'submissions' => [
                'limit' => Plan::Pro->dailySubmissions(),
                'used' => 2,
                'remaining' => Plan::Pro->dailySubmissions() - 2,
            ],
            'invitations' => [
                'limit' => Plan::Pro->invitations(),
                'used' => 1,
                'remaining' => Plan::Pro->invitations() - 1,
            ],
        ]);
});

it('shares no recent submissions for guests', function (): void {
    $middleware = new HandleInertiaRequests();

    $shared = $middleware->share(Request::create('/', 'GET'));

    expect($shared['recentSubmissions'])->toBe([]);
});

it('shares the three most recent submissions of the authenticated user', function (): void {
    $user = User::factory()->create();

    Submission::factory()->for($user)->create(['title' => 'Oldest']);
    Submission::factory()->for($user)->create(['title' => 'Older']);
    $untitled = Submission::factory()->for($user)->create(['title' => null, 'body' => 'Առաջին երկրորդ երրորդ չորրորդ հինգերորդ վեցերորդ յոթերորդ ութերորդ իններորդ']);
    Submission::factory()->for($user)->analyzed()->create(['title' => 'Newest']);
    Submission::factory()->create(['title' => 'Someone else']);

    $middleware = new HandleInertiaRequests();

    $request = Request::create('/', 'GET');
    $request->setUserResolver(fn () => $user);

    $recent = $middleware->share($request)['recentSubmissions'];

    expect($recent)->toBeInstanceOf(Closure::class)
        ->and(array_column($recent(), 'title'))->toBe([
            'Newest',
            'Առաջին երկրորդ երրորդ չորրորդ հինգերորդ վեցերորդ յոթերորդ ութերորդ...',
            'Older',
        ])
        ->and($recent()[0]['state'])->toBe(SubmissionState::Analyzed)
        ->and($recent()[1]['id'])->toBe($untitled->id);
});

it('shares no open polls and no admin url for guests', function (): void {
    $shared = new HandleInertiaRequests()->share(Request::create('/', 'GET'));

    expect($shared['openPollsCount'])->toBe(0)
        ->and($shared['openQuestionsCount'])->toBe(0)
        ->and($shared['adminUrl'])->toBeNull();
});

it('shares the number of published polls the user has not answered', function (): void {
    $user = User::factory()->create();

    $answered = Poll::factory()->published()->withOptions()->create();
    PollVote::factory()->for($user)->create(['poll_option_id' => $answered->options[0]->id]);
    $answeredByOthers = Poll::factory()->published()->withOptions()->create();
    PollVote::factory()->create(['poll_option_id' => $answeredByOthers->options[0]->id]);
    Poll::factory()->published()->create();
    Poll::factory()->create();
    Poll::factory()->closed()->create();

    $request = Request::create('/', 'GET');
    $request->setUserResolver(fn () => $user);

    $count = new HandleInertiaRequests()->share($request)['openPollsCount'];

    expect($count)->toBeInstanceOf(Closure::class)
        ->and($count())->toBe(2);
});

it('shares the number of unresolved factual questions', function (): void {
    AnalysisNote::factory()->question()->count(2)->create();
    AnalysisNote::factory()->question()->create(['status' => QuestionStatus::Resolved]);
    AnalysisNote::factory()->question(QuestionKind::Value)->create();
    AnalysisNote::factory()->create();

    $request = Request::create('/', 'GET');
    $request->setUserResolver(fn () => User::factory()->create());

    $count = new HandleInertiaRequests()->share($request)['openQuestionsCount'];

    expect($count)->toBeInstanceOf(Closure::class)
        ->and($count())->toBe(2);
});

it('shares the admin url with admins only', function (bool $isAdmin): void {
    $request = Request::create('/', 'GET');
    $request->setUserResolver(fn () => User::factory()->create(['is_admin' => $isAdmin]));

    expect(new HandleInertiaRequests()->share($request)['adminUrl'])->toBe($isAdmin ? url('/admin') : null);
})->with([
    'admin' => [true],
    'user' => [false],
]);
