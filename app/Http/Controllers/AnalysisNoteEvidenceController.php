<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateEvidence;
use App\Enums\Stance;
use App\Http\Requests\CreateEvidenceRequest;
use App\Models\AnalysisNote;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

final readonly class AnalysisNoteEvidenceController
{
    public function store(CreateEvidenceRequest $request, AnalysisNote $analysisNote, #[CurrentUser] User $user, CreateEvidence $action): RedirectResponse
    {
        $action->handle(
            $analysisNote,
            $user,
            $request->enum('stance', Stance::class) ?? Stance::Supports,
            $request->string('url')->value(),
            $request->string('quote')->value(),
            $request->filled('explanation') ? $request->string('explanation')->value() : null,
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Thanks. We are checking that the passage is on the page.')]);

        return back();
    }
}
