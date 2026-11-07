<?php

declare(strict_types=1);

use App\Actions\MapSubmissionArgument;
use App\Enums\AnalysisStatus;
use App\Enums\AnalysisType;
use App\Enums\Ethics;
use App\Enums\PollStatus;
use App\Enums\QuestionKind;
use App\Enums\QuestionStatus;
use App\Enums\SubmissionKind;
use App\Enums\SubmissionState;
use App\Models\AnalysisNote;
use App\Models\Poll;
use App\Models\PollVote;
use App\Models\Submission;
use App\Models\SubmissionAnalysis;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Http;

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function mapAssumption(array $overrides = []): array
{
    return [
        'assumption' => 'Ենթադրություն',
        'kind' => 'factual',
        'acceptance' => 'doubtful',
        'assessment' => 'Գնահատական',
        'confidence' => 0.9,
        'gap' => [
            'issue' => 'Բաց',
            'fallacy' => 'False dilemma',
            'strength' => 7,
            'ethics' => 'questionable',
        ],
        'question' => null,
        ...$overrides,
    ];
}

/**
 * @param  list<mixed>  $assumptions
 * @return array<string, mixed>
 */
function mapClaim(string $quote, array $assumptions): array
{
    return [
        'claim' => 'Պնդում',
        'quote' => $quote,
        'reasons' => [],
        'assumptions' => $assumptions,
    ];
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function mapTechnique(array $overrides = []): array
{
    return [
        'name' => 'Appeal to fear',
        'quote' => 'կամ էլ մեր երկիրը կկործանվի',
        'effect' => 'Ազդեցություն',
        'strength' => 8,
        'ethics' => 'questionable',
        'confidence' => 0.9,
        ...$overrides,
    ];
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function argumentMap(array $overrides = []): array
{
    return [
        'text_kind' => 'argument',
        'speaker' => 'Հեղինակ',
        'summary' => 'Ամփոփում',
        'claims' => [],
        'techniques' => [],
        'overall' => ['reasoning' => 'weak', 'comment' => 'Մեկնաբանություն'],
        ...$overrides,
    ];
}

/**
 * @return array<string, mixed>
 */
function valueQuestion(mixed $poll = null): array
{
    return [
        'question' => 'Արդյո՞ք սա արդարացի է։',
        'kind' => 'factual',
        'poll' => $poll,
    ];
}

function analysisOf(Submission $submission, AnalysisType $type): SubmissionAnalysis
{
    return $submission->analyses()->where('type', $type)->sole();
}

it('maps the argument with one request and stores the map', function (): void {
    $map = argumentMap();

    fakeOpenAi($map);

    $submission = Submission::factory()->create();

    $result = resolve(MapSubmissionArgument::class)->handle($submission);

    expect($result->is($submission))->toBeTrue()
        ->and($submission->refresh()->state)->toBe(SubmissionState::Processing)
        ->and($submission->kind)->toBe(SubmissionKind::Argument)
        ->and($submission->argument_map)->toBe($map)
        ->and($submission->analyses()->count())->toBe(3)
        ->and($submission->analyses->every(fn (SubmissionAnalysis $analysis): bool => $analysis->status === AnalysisStatus::Completed
            && $analysis->result === null))->toBeTrue();

    Http::assertSentCount(1);
    Http::assertSent(function (Request $request) use ($submission): bool {
        $system = $request['messages'][0]['content'];
        $schema = $request['response_format']['json_schema'];
        $assumption = $schema['schema']['properties']['claims']['items']['properties']['assumptions']['items'];

        return $request['messages'][1] === ['role' => 'user', 'content' => $submission->body]
            && $request['messages'][0]['role'] === 'system'
            && str_contains($system, 'False dilemma, Slippery slope')
            && str_contains($system, 'Appeal to emotion, Appeal to fear')
            && ! str_contains($system, '{FALLACIES}')
            && ! str_contains($system, '{TECHNIQUES}')
            && $schema['name'] === 'argument_map'
            && $schema['strict'] === true
            && $schema['schema']['properties']['text_kind']['enum'] === ['argument', 'informational', 'personal']
            && $assumption['properties']['gap']['anyOf'][1]['properties']['fallacy']['anyOf'][1]['enum'] === array_keys(AnalysisType::Fallacy->catalog())
            && $schema['schema']['properties']['techniques']['items']['properties']['name']['enum'] === array_keys(AnalysisType::Rhetoric->catalog());
    });
});

it('turns confident reasoning gaps into findings', function (): void {
    fakeOpenAi(argumentMap(['claims' => [
        mapClaim('«Եթե դու համաձայն չես կառավարության հետ, ուրեմն դու հայրենիքի թշնամի ես։»', [
            mapAssumption(['gap' => ['issue' => 'Կեղծ ընտրություն', 'fallacy' => 'False dilemma', 'strength' => 12, 'ethics' => 'unethical']]),
            mapAssumption(['gap' => ['issue' => 'Անհայտ', 'fallacy' => 'Made-up fallacy', 'strength' => 0, 'ethics' => 'neutral']]),
            mapAssumption(['acceptance' => 'widely_shared']),
            mapAssumption(['gap' => null, 'confidence' => 0.95]),
            mapAssumption(['confidence' => 0.3]),
            mapAssumption(['confidence' => 'sure']),
            mapAssumption(['gap' => 'nope', 'assessment' => ' ']),
            'not an assumption',
        ]),
        mapClaim('Մեր քաղաքական գործիչները մշտապես խոստանում են', [
            mapAssumption(['confidence' => '0.85', 'gap' => ['fallacy' => null, 'strength' => 'strong', 'ethics' => 'weird']]),
        ]),
        mapClaim('Այս նախադասությունը տեքստում չկա', [mapAssumption()]),
        'not a claim',
    ]]));

    $submission = Submission::factory()->create();

    resolve(MapSubmissionArgument::class)->handle($submission);

    $notes = analysisOf($submission, AnalysisType::Fallacy)->notes()->orderBy('id')->get();

    expect($notes)->toHaveCount(3)
        ->and($notes[0]->annotation_id)->toBe('fallacy-1')
        ->and($notes[0]->label)->toBe('Մեր քաղաքական գործիչները մշտապես խոստանում են')
        ->and($notes[0]->aspect_label)->toBe('Տրամաբանական բաց (Reasoning gap)')
        ->and($notes[0]->text)->toBe('Գնահատական')
        ->and($notes[0]->ethics)->toBe(Ethics::Neutral)
        ->and($notes[0]->strength)->toBe(5)
        ->and($notes[0]->question_kind)->toBeNull()
        ->and($notes[0]->status)->toBeNull()
        ->and($notes[1]->annotation_id)->toBe('fallacy-2')
        ->and($notes[1]->label)->toBe('Եթե դու համաձայն չես կառավարության հետ, ուրեմն դու հայրենիքի թշնամի ես')
        ->and($notes[1]->aspect_label)->toBe('Կեղծ երկընտրանք (False dilemma)')
        ->and($notes[1]->text)->toBe('Կեղծ ընտրություն')
        ->and($notes[1]->ethics)->toBe(Ethics::Unethical)
        ->and($notes[1]->strength)->toBe(10)
        ->and($notes[2]->aspect_label)->toBe('Տրամաբանական բաց (Reasoning gap)')
        ->and($notes[2]->text)->toBe('Անհայտ')
        ->and($notes[2]->strength)->toBe(1)
        ->and(analysisOf($submission, AnalysisType::Questions)->notes()->count())->toBe(0)
        ->and(analysisOf($submission, AnalysisType::Rhetoric)->notes()->count())->toBe(0);
});

it('asks open questions where the model is not certain', function (): void {
    fakeOpenAi(argumentMap(['claims' => [
        mapClaim('Բոլորը գիտեն, որ ընդդիմությունը վճարվում է արտասահմանից', [
            mapAssumption(['confidence' => 0.6, 'question' => ['question' => 'Ի՞նչ ապացույցներ կան։', 'kind' => 'factual', 'poll' => ['question' => 'Հարց', 'options' => ['Այո', 'Ոչ']]]]),
            mapAssumption(['confidence' => 0.5, 'gap' => null, 'question' => ['question' => 'Ո՞վ է վճարում։', 'kind' => 'unknown', 'poll' => null]]),
            mapAssumption(['confidence' => 0.9, 'question' => ['question' => 'Վստա՞հ ենք։', 'kind' => 'factual', 'poll' => null]]),
            mapAssumption(['confidence' => 0.6, 'question' => null]),
            mapAssumption(['confidence' => 0.6, 'question' => 'not a question']),
            mapAssumption(['confidence' => 0.6, 'question' => ['question' => ' ', 'kind' => 'factual', 'poll' => null]]),
            mapAssumption(['confidence' => 0.4, 'question' => ['question' => 'Թույլ հարց', 'kind' => 'factual', 'poll' => null]]),
        ]),
    ]]));

    $submission = Submission::factory()->create();

    resolve(MapSubmissionArgument::class)->handle($submission);

    $questions = analysisOf($submission, AnalysisType::Questions)->notes()->orderBy('id')->get();
    $finding = analysisOf($submission, AnalysisType::Fallacy)->notes()->sole();

    expect($questions)->toHaveCount(2)
        ->and($questions[0]->annotation_id)->toBe('questions-1')
        ->and($questions[0]->label)->toBe('Բոլորը գիտեն, որ ընդդիմությունը վճարվում է արտասահմանից')
        ->and($questions[0]->aspect_label)->toBe('Ի՞նչ ապացույցներ կան։')
        ->and($questions[0]->text)->toBe('Բաց')
        ->and($questions[0]->ethics)->toBe(Ethics::Questionable)
        ->and($questions[0]->strength)->toBe(7)
        ->and($questions[0]->question_kind)->toBe(QuestionKind::Factual)
        ->and($questions[0]->status)->toBe(QuestionStatus::Open)
        ->and($questions[1]->aspect_label)->toBe('Ո՞վ է վճարում։')
        ->and($questions[1]->text)->toBe('Գնահատական')
        ->and($questions[1]->ethics)->toBe(Ethics::Neutral)
        ->and($questions[1]->question_kind)->toBe(QuestionKind::Factual)
        ->and($finding->aspect_label)->toBe('Կեղծ երկընտրանք (False dilemma)')
        ->and(Poll::query()->count())->toBe(0);
});

it('always asks contested values as value questions with a poll draft', function (): void {
    fakeOpenAi(argumentMap(['claims' => [
        mapClaim('Ով դեմ է այս ծրագրին, նա պարզապես չի սիրում իր ժողովրդին', [
            mapAssumption([
                'kind' => 'value',
                'acceptance' => 'contested',
                'confidence' => 0.95,
                'question' => valueQuestion([
                    'question' => 'Կարելի՞ է սիրել երկիրը և դեմ լինել բարեփոխմանը։',
                    'options' => [' Այո ', 'Ոչ', ' ', 3, 'Կախված է'],
                    'mirror' => ['question' => 'Հայելային հարց', 'options' => ['Ա', 'Բ']],
                ]),
            ]),
            mapAssumption(['kind' => 'value', 'acceptance' => 'contested', 'confidence' => 0.1, 'gap' => null, 'question' => valueQuestion()]),
        ]),
    ]]));

    $submission = Submission::factory()->create();

    resolve(MapSubmissionArgument::class)->handle($submission);

    $questions = analysisOf($submission, AnalysisType::Questions)->notes()->orderBy('id')->get();
    $poll = $questions[0]->poll;

    expect(analysisOf($submission, AnalysisType::Fallacy)->notes()->count())->toBe(0)
        ->and($questions)->toHaveCount(2)
        ->and($questions[0]->aspect_label)->toBe('Արդյո՞ք սա արդարացի է։')
        ->and($questions[0]->question_kind)->toBe(QuestionKind::Value)
        ->and($questions[0]->status)->toBe(QuestionStatus::Open)
        ->and($questions[1]->question_kind)->toBe(QuestionKind::Value)
        ->and($questions[1]->text)->toBe('Գնահատական')
        ->and($questions[1]->strength)->toBe(5)
        ->and($questions[1]->poll)->toBeNull()
        ->and($poll->question)->toBe('Կարելի՞ է սիրել երկիրը և դեմ լինել բարեփոխմանը։')
        ->and($poll->status)->toBe(PollStatus::Draft)
        ->and($poll->options->pluck('label')->all())->toBe(['Այո', 'Ոչ', 'Կախված է'])
        ->and($poll->mirror->question)->toBe('Հայելային հարց')
        ->and($poll->mirror->analysis_note_id)->toBe($questions[0]->id)
        ->and($poll->mirror->options->pluck('label')->all())->toBe(['Ա', 'Բ'])
        ->and(Poll::query()->count())->toBe(2);
});

it('drafts a poll only when it has a question and at least two options', function (mixed $draft, ?string $question, ?string $mirror): void {
    fakeOpenAi(argumentMap(['claims' => [
        mapClaim('Մեր նախնիները երբեք թույլ չէին տա', [
            mapAssumption(['kind' => 'value', 'acceptance' => 'contested', 'question' => valueQuestion($draft)]),
        ]),
    ]]));

    $submission = Submission::factory()->create();

    resolve(MapSubmissionArgument::class)->handle($submission);

    $poll = analysisOf($submission, AnalysisType::Questions)->notes()->sole()->poll;

    expect($poll?->question)->toBe($question)
        ->and($poll?->mirror?->question)->toBe($mirror);
})->with([
    'not an object' => ['poll', null, null],
    'missing question' => [['question' => ' ', 'options' => ['Այո', 'Ոչ']], null, null],
    'one option' => [['question' => 'Հարց', 'options' => ['Այո', ' ']], null, null],
    'options not a list' => [['question' => 'Հարց', 'options' => 'Այո'], null, null],
    'no mirror' => [['question' => 'Հարց', 'options' => ['Այո', 'Ոչ'], 'mirror' => null], 'Հարց', null],
    'mirror not an object' => [['question' => 'Հարց', 'options' => ['Այո', 'Ոչ'], 'mirror' => 'Հայելի'], 'Հարց', null],
    'mirror without question' => [['question' => 'Հարց', 'options' => ['Այո', 'Ոչ'], 'mirror' => ['options' => ['Ա', 'Բ']]], 'Հարց', null],
    'mirror with one option' => [['question' => 'Հարց', 'options' => ['Այո', 'Ոչ'], 'mirror' => ['question' => 'Հայելի', 'options' => ['Ա']]], 'Հարց', null],
    'valid mirror' => [['question' => 'Հարց', 'options' => ['Այո', 'Ոչ'], 'mirror' => ['question' => 'Հայելի', 'options' => ['Ա', 'Բ']]], 'Հարց', 'Հայելի'],
]);

it('keeps at most the configured number of the strongest findings in reading order', function (): void {
    config(['analysis.max_findings.fallacy' => 2]);

    fakeOpenAi(argumentMap(['claims' => [
        mapClaim('Մեր քաղաքական գործիչները մշտապես խոստանում են', [mapAssumption(['gap' => ['issue' => 'Առաջին', 'strength' => 9]])]),
        mapClaim('Բոլորը գիտեն, որ ընդդիմությունը վճարվում է արտասահմանից', [mapAssumption(['gap' => ['issue' => 'Թույլ', 'strength' => 2]])]),
        mapClaim('Մեր նախնիները երբեք թույլ չէին տա', [mapAssumption(['gap' => ['issue' => 'Վերջին', 'strength' => 8]])]),
    ]]));

    $submission = Submission::factory()->create();

    resolve(MapSubmissionArgument::class)->handle($submission);

    expect(analysisOf($submission, AnalysisType::Fallacy)->notes()->orderBy('id')->pluck('text')->all())->toBe(['Առաջին', 'Վերջին']);
});

it('stores confident, verbatim persuasion techniques without overlaps', function (): void {
    fakeOpenAi(argumentMap(['techniques' => [
        mapTechnique(['name' => 'Appeal to history or legacy', 'quote' => 'Մեր նախնիները երբեք թույլ չէին տա', 'strength' => 20, 'ethics' => 'weird']),
        mapTechnique(['strength' => 'high']),
        mapTechnique(['name' => 'Hyperbole', 'quote' => 'մեր երկիրը կկործանվի', 'strength' => 3]),
        mapTechnique(['name' => 'Made-up technique', 'quote' => 'Բոլորը գիտեն']),
        mapTechnique(['name' => ' ']),
        mapTechnique(['quote' => 'Մեր քաղաքական գործիչները', 'effect' => ' ']),
        mapTechnique(['quote' => 'Այս նախադասությունը տեքստում չկա']),
        mapTechnique(['quote' => 'Մեր քաղաքական գործիչները', 'confidence' => 0.5]),
        mapTechnique(['quote' => 'Մեր քաղաքական գործիչները', 'confidence' => null]),
        'not a technique',
    ]]));

    $submission = Submission::factory()->create();

    resolve(MapSubmissionArgument::class)->handle($submission);

    $notes = analysisOf($submission, AnalysisType::Rhetoric)->notes()->orderBy('id')->get();

    expect($notes)->toHaveCount(2)
        ->and($notes[0]->annotation_id)->toBe('rhetoric-1')
        ->and($notes[0]->label)->toBe('կամ էլ մեր երկիրը կկործանվի')
        ->and($notes[0]->aspect_label)->toBe('Վախի կոչ (Appeal to fear)')
        ->and($notes[0]->text)->toBe('Ազդեցություն')
        ->and($notes[0]->ethics)->toBe(Ethics::Questionable)
        ->and($notes[0]->strength)->toBe(5)
        ->and($notes[1]->label)->toBe('Մեր նախնիները երբեք թույլ չէին տա')
        ->and($notes[1]->aspect_label)->toBe('Պատմության և ժառանգության կոչ (Appeal to history or legacy)')
        ->and($notes[1]->ethics)->toBe(Ethics::Neutral)
        ->and($notes[1]->strength)->toBe(10);
});

it('does not analyze texts that are not arguments', function (string $kind, SubmissionKind $expected): void {
    fakeOpenAi(argumentMap([
        'text_kind' => $kind,
        'claims' => [mapClaim('Մեր նախնիները երբեք թույլ չէին տա', [mapAssumption()])],
        'techniques' => [mapTechnique()],
    ]));

    $submission = Submission::factory()->create();

    resolve(MapSubmissionArgument::class)->handle($submission);

    expect($submission->refresh()->kind)->toBe($expected)
        ->and(AnalysisNote::query()->count())->toBe(0)
        ->and($submission->analyses->every(fn (SubmissionAnalysis $analysis): bool => $analysis->status === AnalysisStatus::Completed))->toBeTrue();
})->with([
    'informational' => ['informational', SubmissionKind::Informational],
    'personal' => ['personal', SubmissionKind::Personal],
]);

it('treats an unknown text kind as an argument', function (): void {
    fakeOpenAi(argumentMap([
        'text_kind' => 'poem',
        'claims' => [mapClaim('Մեր նախնիները երբեք թույլ չէին տա', [mapAssumption()])],
    ]));

    $submission = Submission::factory()->create();

    resolve(MapSubmissionArgument::class)->handle($submission);

    expect($submission->refresh()->kind)->toBe(SubmissionKind::Argument)
        ->and(analysisOf($submission, AnalysisType::Fallacy)->notes()->count())->toBe(1);
});

it('replaces the notes and polls of a previous run', function (): void {
    $submission = Submission::factory()->analyzed()->create();
    $analysis = SubmissionAnalysis::factory()->for($submission)->create(['type' => AnalysisType::Questions, 'result' => ['old' => true]]);
    $oldNote = AnalysisNote::factory()->question(QuestionKind::Value)->create(['submission_analysis_id' => $analysis->id]);
    $poll = Poll::factory()->published()->withOptions()->create(['analysis_note_id' => $oldNote->id]);
    Poll::factory()->create(['analysis_note_id' => $oldNote->id, 'mirror_of_id' => $poll->id]);
    PollVote::factory()->create(['poll_option_id' => $poll->options->first()->id]);

    fakeOpenAi(argumentMap(['claims' => [
        mapClaim('Մեր նախնիները երբեք թույլ չէին տա', [mapAssumption(['confidence' => 0.6, 'question' => valueQuestion()])]),
    ]]));

    resolve(MapSubmissionArgument::class)->handle($submission);

    expect(AnalysisNote::query()->whereKey($oldNote->id)->exists())->toBeFalse()
        ->and(Poll::query()->count())->toBe(0)
        ->and(PollVote::query()->count())->toBe(0)
        ->and($analysis->refresh()->status)->toBe(AnalysisStatus::Completed)
        ->and($analysis->result)->toBeNull()
        ->and($analysis->notes()->sole()->annotation_id)->toBe('questions-1')
        ->and($submission->analyses()->count())->toBe(3)
        ->and($submission->refresh()->state)->toBe(SubmissionState::Analyzed);
});

it('marks every analysis as failed when the request fails', function (Closure $fake, string $exception): void {
    Exceptions::fake();

    config(['services.openai.key' => 'test-key', 'services.openai.url' => 'https://api.openai.com/v1']);

    $fake();

    $submission = Submission::factory()->create();
    $analysis = SubmissionAnalysis::factory()->for($submission)->create(['type' => AnalysisType::Rhetoric]);
    $note = AnalysisNote::factory()->for($analysis, 'analysis')->create();

    $result = resolve(MapSubmissionArgument::class)->handle($submission);

    expect($result->kind)->toBeNull()
        ->and($result->argument_map)->toBeNull()
        ->and($submission->analyses()->count())->toBe(3)
        ->and($submission->analyses->every(fn (SubmissionAnalysis $analysis): bool => $analysis->status === AnalysisStatus::Failed))->toBeTrue()
        ->and($note->exists)->toBeTrue()
        ->and(AnalysisNote::query()->whereKey($note->id)->exists())->toBeTrue();

    Exceptions::assertReported($exception);
})->with([
    'connection error' => [fn () => Http::fake(['*' => Http::failedConnection()]), ConnectionException::class],
    'server error' => [fn () => Http::fake(['*' => Http::response('Error', 500)]), RequestException::class],
    'invalid json' => [fn () => fakeOpenAi('{invalid'), JsonException::class],
]);
