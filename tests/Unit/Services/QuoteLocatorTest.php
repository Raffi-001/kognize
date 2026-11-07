<?php

declare(strict_types=1);

use App\Services\QuoteLocator;

$body = "Մեր ազգի ներկան թշվառ է։ Եթե հույս ունինք ապագայի վերա,\nնորա երևեցուցիչը դուք եք։";

it('finds a quote and returns it exactly as in the text', function (string $quote, string $expected) use ($body): void {
    $location = resolve(QuoteLocator::class)->locate($body, $quote);

    expect($location)->not->toBeNull()
        ->and($location['text'])->toBe($expected)
        ->and(mb_substr($body, $location['offset'], mb_strlen($location['text'])))->toBe($expected);
})->with([
    'exact' => ['Մեր ազգի ներկան թշվառ է', 'Մեր ազգի ներկան թշվառ է'],
    'surrounding quotes and punctuation' => ['«Մեր ազգի ներկան թշվառ է։»', 'Մեր ազգի ներկան թշվառ է'],
    'different whitespace' => ['ապագայի վերա, նորա երևեցուցիչը', "ապագայի վերա,\nնորա երևեցուցիչը"],
    'ellipsis keeps the longest part' => ['Եթե հույս ունինք … դուք եք', 'Եթե հույս ունինք'],
    'three dots' => ['ազգի ներկան...դուք եք', 'ազգի ներկան'],
]);

it('returns null when the quote is not in the text', function (string $quote) use ($body): void {
    expect(resolve(QuoteLocator::class)->locate($body, $quote))->toBeNull();
})->with([
    'missing' => ['Այս տեքստը այնտեղ չկա'],
    'too short' => ['է։'],
    'empty' => [''],
]);
