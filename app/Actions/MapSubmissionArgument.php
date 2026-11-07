<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AnalysisStatus;
use App\Enums\AnalysisType;
use App\Enums\Ethics;
use App\Enums\QuestionKind;
use App\Enums\QuestionStatus;
use App\Enums\SubmissionKind;
use App\Enums\SubmissionState;
use App\Models\AnalysisNote;
use App\Models\Poll;
use App\Models\Submission;
use App\Models\SubmissionAnalysis;
use App\Services\OpenAi;
use App\Services\QuoteLocator;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\DB;
use JsonException;

/**
 * Reconstructs the argument of a submission in one model call and turns it
 * into findings: reasoning gaps the model is confident about, open questions
 * where it is not, and persuasion techniques.
 *
 * @phpstan-type Note array{label: string, aspect_label: string, text: string, ethics: Ethics, strength: int, question_kind?: QuestionKind, status?: QuestionStatus}
 * @phpstan-type PollDraft array{question: string, options: list<string>, mirror: array{question: string, options: list<string>}|null}
 * @phpstan-type Candidate array{start: int, end: int, score: float, note: Note, poll: PollDraft|null}
 */
final readonly class MapSubmissionArgument
{
    public function __construct(
        private OpenAi $openAi,
        private QuoteLocator $quotes,
        private CreatePollDraft $createPollDraft,
    ) {}

    public function handle(Submission $submission): Submission
    {
        if ($submission->state === SubmissionState::Pending) {
            $submission->update(['state' => SubmissionState::Processing]);
        }

        $analyses = [];

        foreach (AnalysisType::cases() as $type) {
            $analyses[$type->value] = $submission->analyses()->firstOrCreate(['type' => $type]);
            $analyses[$type->value]->update(['status' => AnalysisStatus::Running]);
        }

        try {
            $map = $this->openAi->structured($this->instructions(), $submission->body, $this->responseFormat());
        } catch (ConnectionException|RequestException|JsonException $exception) {
            report($exception);

            foreach ($analyses as $analysis) {
                $analysis->update(['status' => AnalysisStatus::Failed]);
            }

            return $submission;
        }

        $kind = SubmissionKind::tryFrom($this->string($map, 'text_kind') ?? '') ?? SubmissionKind::Argument;
        $isArgument = $kind === SubmissionKind::Argument;

        $findings = [
            AnalysisType::Fallacy->value => $isArgument ? $this->assumptionFindings($submission->body, $map, questions: false) : [],
            AnalysisType::Questions->value => $isArgument ? $this->assumptionFindings($submission->body, $map, questions: true) : [],
            AnalysisType::Rhetoric->value => $isArgument ? $this->techniques($submission->body, $map) : [],
        ];

        DB::transaction(function () use ($submission, $analyses, $findings, $kind, $map): void {
            $submission->update(['kind' => $kind, 'argument_map' => $map]);

            foreach ($analyses as $type => $analysis) {
                $this->replaceNotes($analysis, $findings[$type]);
            }
        });

        return $submission;
    }

    /**
     * @param  list<Candidate>  $candidates
     */
    private function replaceNotes(SubmissionAnalysis $analysis, array $candidates): void
    {
        $analysis->notes()->each(function (AnalysisNote $note): void {
            Poll::query()->where('analysis_note_id', $note->id)->delete();
            $note->delete();
        });

        foreach ($candidates as $index => $candidate) {
            $note = $analysis->notes()->create([
                'annotation_id' => sprintf('%s-%d', $analysis->type->value, $index + 1),
                ...$candidate['note'],
            ]);

            if ($candidate['poll'] !== null) {
                $this->createPollDraft->handle($note, $candidate['poll']);
            }
        }

        $analysis->update(['status' => AnalysisStatus::Completed, 'result' => null]);
    }

    /**
     * Reasoning gaps the model is confident about become findings; less certain
     * ones become open questions. Widely shared premises are never flagged.
     *
     * @param  array<string, mixed>  $map
     * @return list<Candidate>
     */
    private function assumptionFindings(string $body, array $map, bool $questions): array
    {
        $catalog = AnalysisType::Fallacy->catalog();
        $findingConfidence = config()->float('analysis.confidence.finding');
        $questionConfidence = config()->float('analysis.confidence.question');
        $candidates = [];

        foreach ($this->list($map, 'claims') as $claim) {
            $location = $this->quotes->locate($body, $this->string($claim, 'quote') ?? '');

            if ($location === null) {
                continue;
            }

            foreach ($this->list($claim, 'assumptions') as $assumption) {
                $gap = is_array($assumption['gap'] ?? null) ? $assumption['gap'] : null;
                $question = is_array($assumption['question'] ?? null) ? $assumption['question'] : null;
                $confidence = is_numeric($assumption['confidence'] ?? null) ? (float) $assumption['confidence'] : 0.0;
                $acceptance = $this->string($assumption, 'acceptance');
                $issue = ($gap === null ? null : $this->string($gap, 'issue')) ?? $this->string($assumption, 'assessment');

                // A contested value is a disagreement to ask about, never a flaw to assert.
                $isValueDisagreement = $acceptance === 'contested' && $this->string($assumption, 'kind') === 'value';

                if ($acceptance === 'widely_shared' || $issue === null) {
                    continue;
                }

                $strength = max(1, min(10, is_numeric($gap['strength'] ?? null) ? (int) $gap['strength'] : 5));
                $note = [
                    'label' => $location['text'],
                    'text' => $issue,
                    'ethics' => Ethics::tryFrom($gap === null ? '' : ($this->string($gap, 'ethics') ?? '')) ?? Ethics::Neutral,
                    'strength' => $strength,
                ];
                $poll = null;
                $asQuestion = $isValueDisagreement || ($confidence >= $questionConfidence && $confidence < $findingConfidence);

                if (! $questions && ! $isValueDisagreement && $gap !== null && $confidence >= $findingConfidence) {
                    $fallacy = $this->string($gap, 'fallacy');
                    $note['aspect_label'] = $fallacy !== null && isset($catalog[$fallacy])
                        ? sprintf('%s (%s)', $catalog[$fallacy]['armenian'], $fallacy)
                        : 'Տրամաբանական բաց (Reasoning gap)';
                } elseif ($questions && $asQuestion && $question !== null && ($heading = $this->string($question, 'question')) !== null) {
                    $kind = $isValueDisagreement
                        ? QuestionKind::Value
                        : (QuestionKind::tryFrom($this->string($question, 'kind') ?? '') ?? QuestionKind::Factual);
                    $note = [...$note, 'aspect_label' => $heading, 'question_kind' => $kind, 'status' => QuestionStatus::Open];
                    $poll = $kind === QuestionKind::Value ? $this->pollDraft($question['poll'] ?? null) : null;
                } else {
                    continue;
                }

                $candidates[] = [
                    'start' => $location['offset'],
                    'end' => $location['offset'] + mb_strlen($location['text']),
                    'score' => $confidence * $strength,
                    'note' => $note,
                    'poll' => $poll,
                ];
            }
        }

        $type = $questions ? AnalysisType::Questions : AnalysisType::Fallacy;

        return $this->strongest($candidates, $type->maxFindings(), allowOverlap: true);
    }

    /**
     * @param  array<string, mixed>  $map
     * @return list<Candidate>
     */
    private function techniques(string $body, array $map): array
    {
        $catalog = AnalysisType::Rhetoric->catalog();
        $minimum = config()->float('analysis.confidence.technique');
        $candidates = [];

        foreach ($this->list($map, 'techniques') as $technique) {
            $name = $this->string($technique, 'name');
            $effect = $this->string($technique, 'effect');
            $confidence = is_numeric($technique['confidence'] ?? null) ? (float) $technique['confidence'] : 0.0;
            $location = $this->quotes->locate($body, $this->string($technique, 'quote') ?? '');

            if ($name === null || ! isset($catalog[$name]) || $effect === null || $location === null || $confidence < $minimum) {
                continue;
            }

            $strength = max(1, min(10, is_numeric($technique['strength'] ?? null) ? (int) $technique['strength'] : 5));

            $candidates[] = [
                'start' => $location['offset'],
                'end' => $location['offset'] + mb_strlen($location['text']),
                'score' => $confidence * $strength,
                'note' => [
                    'label' => $location['text'],
                    'aspect_label' => sprintf('%s (%s)', $catalog[$name]['armenian'], $name),
                    'text' => $effect,
                    'ethics' => Ethics::tryFrom($this->string($technique, 'ethics') ?? '') ?? Ethics::Neutral,
                    'strength' => $strength,
                ],
                'poll' => null,
            ];
        }

        return $this->strongest($candidates, AnalysisType::Rhetoric->maxFindings(), allowOverlap: false);
    }

    /**
     * Keeps the highest-scoring candidates, optionally without overlapping
     * passages, and returns them in reading order.
     *
     * @param  list<Candidate>  $candidates
     * @return list<Candidate>
     */
    private function strongest(array $candidates, int $limit, bool $allowOverlap): array
    {
        usort($candidates, fn (array $a, array $b): int => $b['score'] <=> $a['score']);

        $kept = [];

        foreach ($candidates as $candidate) {
            $overlaps = ! $allowOverlap && array_filter(
                $kept,
                fn (array $other): bool => $candidate['start'] < $other['end'] && $other['start'] < $candidate['end'],
            ) !== [];

            if (! $overlaps && count($kept) < $limit) {
                $kept[] = $candidate;
            }
        }

        usort($kept, fn (array $a, array $b): int => $a['start'] <=> $b['start']);

        return $kept;
    }

    /**
     * @return PollDraft|null
     */
    private function pollDraft(mixed $poll): ?array
    {
        if (! is_array($poll)) {
            return null;
        }

        $question = $this->string($poll, 'question');
        $options = $this->options($poll);

        if ($question === null || count($options) < 2) {
            return null;
        }

        $mirror = is_array($poll['mirror'] ?? null) ? $poll['mirror'] : null;
        $mirrorQuestion = $mirror === null ? null : $this->string($mirror, 'question');
        $mirrorOptions = $mirror === null ? [] : $this->options($mirror);

        return [
            'question' => $question,
            'options' => $options,
            'mirror' => $mirrorQuestion !== null && count($mirrorOptions) >= 2
                ? ['question' => $mirrorQuestion, 'options' => $mirrorOptions]
                : null,
        ];
    }

    /**
     * @param  array<mixed>  $poll
     * @return list<string>
     */
    private function options(array $poll): array
    {
        $options = is_array($poll['options'] ?? null) ? $poll['options'] : [];

        return array_values(array_filter(array_map(
            fn (mixed $option): ?string => is_string($option) && mb_trim($option) !== '' ? mb_trim($option) : null,
            $options,
        )));
    }

    private function instructions(): string
    {
        return str_replace(
            ['{FALLACIES}', '{TECHNIQUES}'],
            [implode(', ', array_keys(AnalysisType::Fallacy->catalog())), implode(', ', array_keys(AnalysisType::Rhetoric->catalog()))],
            config()->string('analysis.prompt'),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function responseFormat(): array
    {
        $text = ['type' => 'string'];
        $ethics = OpenAi::enum(array_column(Ethics::cases(), 'value'));
        $pollQuestion = OpenAi::object(['question' => $text, 'options' => OpenAi::list($text)]);

        return OpenAi::schema('argument_map', OpenAi::object([
            'text_kind' => OpenAi::enum(array_column(SubmissionKind::cases(), 'value')),
            'speaker' => $text,
            'summary' => $text,
            'claims' => OpenAi::list(OpenAi::object([
                'claim' => $text,
                'quote' => $text,
                'reasons' => OpenAi::list(OpenAi::object([
                    'reason' => $text,
                    'kind' => OpenAi::enum(['factual', 'value', 'interpretation']),
                    'support' => OpenAi::enum(['supported', 'asserted']),
                ])),
                'assumptions' => OpenAi::list(OpenAi::object([
                    'assumption' => $text,
                    'kind' => OpenAi::enum(['value', 'factual', 'definitional']),
                    'acceptance' => OpenAi::enum(['widely_shared', 'contested', 'doubtful']),
                    'assessment' => $text,
                    'confidence' => ['type' => 'number'],
                    'gap' => OpenAi::nullable(OpenAi::object([
                        'issue' => $text,
                        'fallacy' => OpenAi::nullable(OpenAi::enum(array_keys(AnalysisType::Fallacy->catalog()))),
                        'strength' => ['type' => 'integer'],
                        'ethics' => $ethics,
                    ])),
                    'question' => OpenAi::nullable(OpenAi::object([
                        'question' => $text,
                        'kind' => OpenAi::enum(array_column(QuestionKind::cases(), 'value')),
                        'poll' => OpenAi::nullable(OpenAi::object([
                            'question' => $text,
                            'options' => OpenAi::list($text),
                            'mirror' => OpenAi::nullable($pollQuestion),
                        ])),
                    ])),
                ])),
            ])),
            'techniques' => OpenAi::list(OpenAi::object([
                'name' => OpenAi::enum(array_keys(AnalysisType::Rhetoric->catalog())),
                'quote' => $text,
                'effect' => $text,
                'strength' => ['type' => 'integer'],
                'ethics' => $ethics,
                'confidence' => ['type' => 'number'],
            ])),
            'overall' => OpenAi::object([
                'reasoning' => OpenAi::enum(['strong', 'mixed', 'weak']),
                'comment' => $text,
            ]),
        ]));
    }

    /**
     * @param  array<mixed>  $data
     * @return list<array<mixed>>
     */
    private function list(array $data, string $key): array
    {
        $items = is_array($data[$key] ?? null) ? $data[$key] : [];

        return array_values(array_filter($items, is_array(...)));
    }

    /**
     * @param  array<mixed>  $data
     */
    private function string(array $data, string $key): ?string
    {
        $value = $data[$key] ?? null;

        return is_scalar($value) && mb_trim((string) $value) !== '' ? mb_trim((string) $value) : null;
    }
}
