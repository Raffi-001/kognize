<?php

declare(strict_types=1);

namespace App\Filament\Resources\Newsletters;

use App\Mail\NewsletterMail;
use App\Models\Newsletter;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;

final class NewsletterActions
{
    /**
     * Show the saved newsletter exactly as recipients will see it.
     */
    public static function preview(): Action
    {
        return Action::make('preview')
            ->icon(Heroicon::OutlinedEye)
            ->color('gray')
            ->modalHeading(fn (Newsletter $record): string => $record->subject)
            ->modalWidth('3xl')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Close')
            ->modalContent(function (Newsletter $record): HtmlString {
                $admin = auth()->user();

                assert($admin instanceof User);

                $html = new NewsletterMail($record, $admin)->render();

                return new HtmlString(sprintf(
                    '<iframe title="Newsletter preview" srcdoc="%s" style="width:100%%;height:70vh;border:0"></iframe>',
                    e($html),
                ));
            });
    }
}
