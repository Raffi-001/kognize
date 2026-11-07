<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\QuestionKind;
use App\Enums\QuestionStatus;
use App\Enums\Stance;
use App\Models\AnalysisNote;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class CreateEvidenceRequest extends FormRequest
{
    /**
     * @return array<string, array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'stance' => ['required', Rule::enum(Stance::class)],
            'url' => ['required', 'string', 'url:http,https', 'max:2048'],
            'quote' => ['required', 'string', 'min:10', 'max:1000'],
            'explanation' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'quote.min' => 'Copy the exact passage from the source that answers the question.',
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $question = $this->route('analysisNote');

                if (! $question instanceof AnalysisNote
                    || $question->question_kind !== QuestionKind::Factual
                    || $question->status !== QuestionStatus::Open) {
                    $validator->errors()->add('quote', 'Evidence can only be added to open factual questions.');
                }
            },
        ];
    }
}
