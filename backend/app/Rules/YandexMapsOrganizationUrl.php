<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class YandexMapsOrganizationUrl implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('The :attribute must be a valid Yandex Maps organization URL.');
            return;
        }

        $host = parse_url($value, PHP_URL_HOST);
        $path = parse_url($value, PHP_URL_PATH);

        if (! $host || ! $path) {
            $fail('The :attribute must be a valid Yandex Maps organization URL.');
            return;
        }

        $isYandexHost = preg_match('/(^|\.)yandex\.(ru|kz|com|by|uz|az|kg|tj|tm)$/i', $host) === 1
            || preg_match('/^maps\.yandex\./i', $host) === 1;

        $looksLikeOrganization = str_contains($path, '/org/')
            || str_contains($path, '/maps/org/')
            || preg_match('/\/maps\/\d+\/[^\/]+\/[^\/]+\/\d+/i', $path) === 1;

        if (! $isYandexHost || ! $looksLikeOrganization) {
            $fail('The :attribute must be a valid Yandex Maps organization URL.');
        }
    }
}
