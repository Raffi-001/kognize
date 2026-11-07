<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\User;
use App\Rules\ArmenianText;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class CreateSubmissionRequest extends FormRequest
{
    /**
     * @return array<string, array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:50000', ArmenianText::fromConfig()],
            'source' => ['nullable', 'url:http,https', 'max:2048'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $user = $this->user();

                assert($user instanceof User);

                if ($user->remainingSubmissionsToday() === 0) {
                    $validator->errors()->add('limit', 'You have used all of your analyses for today.');
                }
            },
        ];
    }
}
