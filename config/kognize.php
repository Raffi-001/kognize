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

];
