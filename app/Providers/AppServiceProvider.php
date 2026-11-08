<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\AnalysisNote;
use App\Models\Comment;
use App\Models\Evidence;
use App\Models\Submission;
use App\Models\User;
use App\Models\UserNote;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        JsonResource::withoutWrapping();

        Relation::enforceMorphMap([
            'user' => User::class,
            'submission' => Submission::class,
            'analysis_note' => AnalysisNote::class,
            'user_note' => UserNote::class,
            'comment' => Comment::class,
            'evidence' => Evidence::class,
        ]);
    }
}
