<?php

declare(strict_types=1);

use App\Rules\ArmenianText;

function armenianTextFails(ArmenianText $rule, mixed $value): bool
{
    $failed = false;

    $rule->validate('body', $value, function () use (&$failed): void {
        $failed = true;
    });

    return $failed;
}

it('builds the rule from config', function (): void {
    config([
        'analysis.min_words' => 2,
        'analysis.max_words' => 3,
        'analysis.min_armenian_ratio' => 0.5,
    ]);

    $rule = ArmenianText::fromConfig();

    expect(armenianTextFails($rule, 'Բարև աշխարհ'))->toBeFalse()
        ->and(armenianTextFails($rule, 'Բարև'))->toBeTrue()
        ->and(armenianTextFails($rule, 'Բարև մեծ գեղեցիկ աշխարհ'))->toBeTrue();
});

it('passes the sample text', function (): void {
    expect(armenianTextFails(ArmenianText::fromConfig(), config('analysis.sample_text')))->toBeFalse();
});

it('counts armenian words', function (): void {
    expect(ArmenianText::wordCount('Բարև, աշխարհ։ Ա hello world'))->toBe(2)
        ->and(ArmenianText::wordCount('hello world'))->toBe(0);
});

it('fails for invalid values', function (mixed $value): void {
    expect(armenianTextFails(new ArmenianText(2, 10, 0.8), $value))->toBeTrue();
})->with([
    'not a string' => [123],
    'empty' => [''],
    'english only' => ['Hello beautiful world'],
    'too much english' => ['Բարև աշխարհ hello beautiful wonderful world'],
    'too few words' => ['Բարև'],
    'too many words' => [str_repeat('Բարև ', 11)],
]);

it('reports the word limits', function (): void {
    $message = null;

    new ArmenianText(2, 10, 0.8)->validate('body', 'Hello', function (string $error) use (&$message): void {
        $message = $error;
    });

    expect($message)->toContain('between 2 and 10 words');
});
