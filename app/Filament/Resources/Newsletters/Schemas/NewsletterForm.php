<?php

declare(strict_types=1);

namespace App\Filament\Resources\Newsletters\Schemas;

use App\Enums\PollStatus;
use App\Enums\QuestionKind;
use App\Enums\QuestionStatus;
use App\Enums\SubmissionState;
use App\Mail\NewsletterMail;
use App\Models\AnalysisNote;
use App\Models\Submission;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

final class NewsletterForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Email')
                    ->description(self::tokensDescription())
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('subject')
                            ->placeholder('Բարև {{ user.first_name }}, ...')
                            ->required()
                            ->maxLength(255),
                        Textarea::make('intro')
                            ->helperText('Optional. Shown above the lists. Markdown works.')
                            ->maxLength(5000)
                            ->rows(3),
                    ]),
                Section::make('Content')
                    ->description('A category with nothing selected is left out of the email, heading included.')
                    ->columnSpanFull()
                    ->schema([
                        Select::make('submissions')
                            ->relationship(
                                'submissions',
                                'title',
                                fn (Builder $query) => $query->where('state', SubmissionState::Analyzed)->latest('id'),
                            )
                            ->getOptionLabelFromRecordUsing(fn (Submission $record): string => $record->displayTitle())
                            ->searchable(['title', 'body'])
                            ->preload()
                            ->multiple(),
                        Select::make('polls')
                            ->relationship(
                                'polls',
                                'question',
                                fn (Builder $query) => $query->where('status', PollStatus::Published)->latest('id'),
                            )
                            ->helperText('Published polls only.')
                            ->searchable()
                            ->preload()
                            ->multiple(),
                        Select::make('questions')
                            ->label('Open questions')
                            ->relationship(
                                'questions',
                                'aspect_label',
                                fn (Builder $query) => $query
                                    ->where('question_kind', QuestionKind::Factual)
                                    ->where('status', QuestionStatus::Open)
                                    ->latest('id'),
                            )
                            ->getOptionLabelFromRecordUsing(fn (AnalysisNote $record): string => Str::limit($record->aspect_label ?? $record->text, 120))
                            ->searchable(['aspect_label', 'text'])
                            ->preload()
                            ->multiple(),
                    ]),
            ]);
    }

    /**
     * Lists the tokens that the subject and intro can use.
     */
    private static function tokensDescription(): HtmlString
    {
        $tokens = collect(NewsletterMail::TOKENS)
            ->map(fn (string $label, string $token): string => sprintf('<code>{{ %s }}</code> %s', e($token), e(mb_strtolower($label))))
            ->implode(', ');

        return new HtmlString(sprintf(
            'Personalize the subject and intro with %s. Each recipient sees their own; the preview shows yours.',
            $tokens,
        ));
    }
}
