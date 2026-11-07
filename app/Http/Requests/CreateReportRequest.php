<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class CreateReportRequest extends FormRequest
{
    /**
     * @var list<string>
     */
    public const array REPORTABLE_TYPES = ['submission', 'analysis_note', 'user_note', 'comment'];

    /**
     * @return array<string, array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'reportable_type' => ['required', 'string', Rule::in(self::REPORTABLE_TYPES)],
            'reportable_id' => ['required', 'integer'],
            'reason' => ['required', 'string', 'max:1000'],
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
                $model = Relation::getMorphedModel($this->string('reportable_type')->value());

                if (! $model::query()->whereKey($this->integer('reportable_id'))->exists()) {
                    $validator->errors()->add('reportable_id', 'The content you are reporting no longer exists.');
                }
            },
        ];
    }
}
