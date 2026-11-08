<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class CreateVoteRequest extends FormRequest
{
    /**
     * @var list<string>
     */
    public const array VOTABLE_TYPES = ['user_note', 'comment', 'evidence'];

    /**
     * @return array<string, array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'votable_type' => ['required', 'string', Rule::in(self::VOTABLE_TYPES)],
            'votable_id' => ['required', 'integer'],
            'value' => ['required', 'integer', Rule::in([1, -1])],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                /** @var class-string<Model> $model */
                $model = Relation::getMorphedModel($this->string('votable_type')->value());

                if (! $model::query()->whereKey($this->integer('votable_id'))->exists()) {
                    $validator->errors()->add('vote', 'This no longer exists.');
                }
            },
        ];
    }
}
