<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

final readonly class ArmenianText implements ValidationRule
{
    private const string ARMENIAN_LETTER = '[\x{0530}-\x{058F}\x{FB13}-\x{FB17}]';

    public function __construct(
        private int $minWords,
        private int $maxWords,
        private float $minRatio,
    ) {}

    public static function fromConfig(): self
    {
        return new self(
            config()->integer('analysis.min_words'),
            config()->integer('analysis.max_words'),
            config()->float('analysis.min_armenian_ratio'),
        );
    }

    public static function wordCount(string $value): int
    {
        return (int) preg_match_all('/'.self::ARMENIAN_LETTER.'{2,}/u', $value);
    }

    /**
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! $this->passes($value)) {
            $fail(sprintf(
                'Տեքստը պետք է լինի հայերեն, պարունակի առնվազն %d և առավելագույնը %d բառ։ The text must be written in Armenian and contain between %d and %d words.',
                $this->minWords,
                $this->maxWords,
                $this->minWords,
                $this->maxWords,
            ));
        }
    }

    private function passes(string $value): bool
    {
        $letters = (int) preg_match_all('/\p{L}/u', $value);
        $armenianLetters = (int) preg_match_all('/'.self::ARMENIAN_LETTER.'/u', $value);

        if ($armenianLetters === 0 || $armenianLetters / $letters < $this->minRatio) {
            return false;
        }

        $words = self::wordCount($value);

        return $words >= $this->minWords && $words <= $this->maxWords;
    }
}
