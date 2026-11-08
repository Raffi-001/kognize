<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\NotificationKind;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateNotificationPreferencesRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'muted' => ['present', 'array'],
            'muted.*' => ['string', 'distinct', Rule::enum(NotificationKind::class)],
            'digest' => ['required', 'boolean'],
        ];
    }
}
