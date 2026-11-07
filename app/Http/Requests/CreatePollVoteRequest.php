<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\PollStatus;
use App\Models\Poll;
use App\Models\PollOption;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class CreatePollVoteRequest extends FormRequest
{
    /**
     * @return array<string, array<mixed>|string>
     */
    public function rules(): array
    {
        $poll = $this->route('poll');

        assert($poll instanceof Poll);

        return [
            'poll_option_id' => ['required', 'integer', Rule::exists(PollOption::class, 'id')->where('poll_id', $poll->id)],
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $poll = $this->route('poll');

                if ($poll instanceof Poll && $poll->status !== PollStatus::Published) {
                    $validator->errors()->add('poll_option_id', 'This question is no longer open for answers.');
                }
            },
        ];
    }
}
