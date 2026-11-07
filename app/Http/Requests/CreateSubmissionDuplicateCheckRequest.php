<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateSubmissionDuplicateCheckRequest extends FormRequest
{
    /**
     * @return array<string, array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'url' => ['required_without:text', 'nullable', 'string', 'url:http,https', 'max:2048'],
            'text' => ['required_without:url', 'nullable', 'string', 'min:50', 'max:50000'],
        ];
    }
}
