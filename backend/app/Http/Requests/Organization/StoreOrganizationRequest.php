<?php

namespace App\Http\Requests\Organization;

use App\Rules\YandexMapsOrganizationUrl;
use Illuminate\Foundation\Http\FormRequest;

class StoreOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'url' => [
                'required',
                'string',
                'max:2000',
                'url',
                new YandexMapsOrganizationUrl(),
            ],
        ];
    }
}
