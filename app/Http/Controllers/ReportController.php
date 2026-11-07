<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateReport;
use App\Http\Requests\CreateReportRequest;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

final readonly class ReportController
{
    public function store(CreateReportRequest $request, #[CurrentUser] User $user, CreateReport $action): RedirectResponse
    {
        $action->handle(
            $user,
            $request->string('reportable_type')->value(),
            $request->integer('reportable_id'),
            $request->string('reason')->value(),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Thanks, our team will review this report.')]);

        return back();
    }
}
