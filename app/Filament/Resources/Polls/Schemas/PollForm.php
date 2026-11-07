<?php

declare(strict_types=1);

namespace App\Filament\Resources\Polls\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

final class PollForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Textarea::make('question')
                    ->label('Question')
                    ->helperText('Neutral and general: no names of people, parties, outlets or events, so it can be answered without knowing the source text.')
                    ->required()
                    ->maxLength(1000)
                    ->rows(3)
                    ->columnSpanFull(),
                Repeater::make('options')
                    ->relationship()
                    ->orderColumn('position')
                    ->simple(
                        TextInput::make('label')
                            ->required()
                            ->maxLength(255),
                    )
                    ->minItems(2)
                    ->maxItems(5)
                    ->defaultItems(3)
                    ->reorderable()
                    ->columnSpanFull(),
            ]);
    }
}
