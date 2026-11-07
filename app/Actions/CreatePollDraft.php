<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PollStatus;
use App\Models\AnalysisNote;
use App\Models\Poll;

final readonly class CreatePollDraft
{
    /**
     * Saves the model's drafted poll, and its mirrored counterpart, for an
     * admin to review before anything is published.
     *
     * @param  array{question: string, options: list<string>, mirror: array{question: string, options: list<string>}|null}  $draft
     */
    public function handle(AnalysisNote $question, array $draft): Poll
    {
        $poll = $this->create($question, $draft['question'], $draft['options']);

        if ($draft['mirror'] !== null) {
            $this->create($question, $draft['mirror']['question'], $draft['mirror']['options'], $poll);
        }

        return $poll;
    }

    /**
     * @param  list<string>  $options
     */
    private function create(AnalysisNote $question, string $text, array $options, ?Poll $mirrorOf = null): Poll
    {
        $poll = Poll::query()->create([
            'analysis_note_id' => $question->id,
            'mirror_of_id' => $mirrorOf?->id,
            'question' => $text,
            'status' => PollStatus::Draft,
        ]);

        foreach ($options as $position => $label) {
            $poll->options()->create(['label' => $label, 'position' => $position]);
        }

        return $poll;
    }
}
