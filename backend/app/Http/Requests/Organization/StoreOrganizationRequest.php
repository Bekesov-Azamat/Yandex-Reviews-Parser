<?php

namespace App\Http\Requests\Organization;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'url' => [
                'required',
                'string',
                'max:2000',
                'url',
                'regex:/^https?:\/\/(yandex\.[a-z.]+|maps\.yandex\.[a-z.]+|yandex\.kz|yandex\.ru)\/.+/i',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'url.regex' => 'The URL must be a valid Yandex Maps organization link.',
        ];
    }
}
