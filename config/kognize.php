<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Invite Only
    |--------------------------------------------------------------------------
    |
    | When enabled, new users need a valid invitation code that was sent to
    | the same email address in order to create an account.
    |
    */

    'invite_only' => (bool) env('KOGNIZE_INVITE_ONLY', true),

    /*
    |--------------------------------------------------------------------------
    | Plans
    |--------------------------------------------------------------------------
    |
    | The number of submissions a user may analyze per day and the total
    | number of invitations they may send, keyed by plan.
    |
    */

    'plans' => [
        'standard' => [
            'daily_submissions' => 3,
            'invitations' => 2,
        ],
        'pro' => [
            'daily_submissions' => 20,
            'invitations' => 10,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Reputation
    |--------------------------------------------------------------------------
    |
    | Reputation is earned only from votes other people cast on your community
    | notes, sources and comments. Everyone starts at 1 and never drops below
    | it. Upvote earnings are capped per day, and votes between people who
    | invited one another earn nothing.
    |
    */

    'reputation' => [
        'points' => [
            'note_upvoted' => 10,
            'note_downvoted' => -2,
            'evidence_upvoted' => 10,
            'evidence_downvoted' => -2,
            'comment_upvoted' => 2,
            'comment_downvoted' => -1,
            'downvote_cast' => -1,
        ],
        'daily_cap' => 200,
    ],

    /*
    |--------------------------------------------------------------------------
    | Privileges
    |--------------------------------------------------------------------------
    |
    | The reputation needed for each privilege. Thresholds are low while the
    | community is small; raise them as it grows.
    |
    */

    'privileges' => [
        'vote_up' => 1,
        'vote_down' => 50,
        'extra_analysis' => 200,
        'extra_invitations' => 500,
        'trusted' => 1000,
    ],

];
