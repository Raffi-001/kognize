<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Tables;

use App\Actions\SendNewsletter;
use App\Enums\Plan;
use App\Models\Newsletter;
use App\Models\User;
use Filament\Actions\BulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('plan')
                    ->badge(),
                TextColumn::make('reputation')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('is_admin')
                    ->label('Admin')
                    ->boolean(),
                IconColumn::make('verified')
                    ->boolean()
                    ->state(fn (User $record): bool => $record->email_verified_at !== null),
                TextColumn::make('referrer.name')
                    ->label('Invited by')
                    ->placeholder('—'),
                TextColumn::make('referees_count')
                    ->label('Invited')
                    ->counts('referees')
                    ->sortable(),
                TextColumn::make('submissions_count')
                    ->label('Analyses')
                    ->counts('submissions')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Joined')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('plan')
                    ->options(Plan::class),
                TernaryFilter::make('is_admin')
                    ->label('Administrator'),
                TernaryFilter::make('email_verified_at')
                    ->label('Verified')
                    ->nullable(),
                TernaryFilter::make('referrer_id')
                    ->label('Invited by someone')
                    ->nullable(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                self::sendNewsletter(),
            ]);
    }

    private static function sendNewsletter(): BulkAction
    {
        return BulkAction::make('sendNewsletter')
            ->label('Send newsletter')
            ->icon(Heroicon::OutlinedEnvelope)
            ->modalDescription('The newsletter is emailed to every selected user.')
            ->modalSubmitActionLabel('Send')
            ->schema([
                Select::make('newsletter_id')
                    ->label('Newsletter')
                    ->options(fn (): array => Newsletter::query()->latest('id')->pluck('subject', 'id')->all())
                    ->searchable()
                    ->required(),
            ])
            ->action(function (Collection $records, array $data, SendNewsletter $send): void {
                $newsletter = Newsletter::query()->whereKey($data['newsletter_id'])->firstOrFail();

                $count = $send->handle($newsletter, $records->whereInstanceOf(User::class));

                Notification::make()
                    ->title(sprintf('Sending “%s” to %d %s', $newsletter->subject, $count, Str::plural('user', $count)))
                    ->success()
                    ->send();
            })
            ->deselectRecordsAfterCompletion();
    }
}
