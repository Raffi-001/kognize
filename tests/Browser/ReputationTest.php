<?php

declare(strict_types=1);

use App\Actions\CastVote;
use App\Models\User;
use App\Models\UserNote;

it('lets readers upvote a community note and see the author gain reputation', function (): void {
    $note = UserNote::factory()->create(['label' => null, 'text' => 'Helpful context']);
    $reader = User::factory()->withoutTwoFactor()->create();

    $this->actingAs($reader);

    $page = visit(route('submissions.show', $note->submission));

    $page->click('[data-test="vote-user_note-'.$note->id.'"] [data-test="vote-up"]')
        ->assertSeeIn('[data-test="vote-user_note-'.$note->id.'"] [data-test="vote-score"]', '1')
        ->assertNoJavaScriptErrors();

    expect($note->refresh()->score)->toBe(1)
        ->and($note->user->refresh()->reputation)->toBe(11);

    $page->click('[data-test="vote-user_note-'.$note->id.'"] [data-test="vote-up"]')
        ->assertSeeIn('[data-test="vote-user_note-'.$note->id.'"] [data-test="vote-score"]', '0');

    expect($note->refresh()->score)->toBe(0);
});

it('explains when a vote is not allowed yet', function (): void {
    $note = UserNote::factory()->create(['label' => null]);

    $this->actingAs(User::factory()->withoutTwoFactor()->create());

    visit(route('submissions.show', $note->submission))
        ->click('[data-test="vote-user_note-'.$note->id.'"] [data-test="vote-down"]')
        ->assertSee('You need 50 reputation to downvote.')
        ->assertNoJavaScriptErrors();
});

it('shows profiles and the leaderboard', function (): void {
    $note = UserNote::factory()->create();
    $author = $note->user;
    $author->update(['name' => 'Ani']);

    $reader = User::factory()->withoutTwoFactor()->create();
    resolve(CastVote::class)->handle($reader, $note, 1);

    $this->actingAs($reader);

    visit(route('leaderboard.index'))
        ->assertSee('Leaderboard')
        ->assertSee('Ani')
        ->click('@leader')
        ->assertPathIs('/u/'.$author->id)
        ->assertSeeIn('@profile-reputation', '11')
        ->assertSee('Annotator')
        ->assertSee('Next: Vote down')
        ->assertNoJavaScriptErrors();
});
