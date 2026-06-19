<?php

namespace App\Services\YandexMaps\Exceptions;

class YandexMapsUnavailableException extends YandexMapsParserException
{
    public function __construct(
        string $message = 'Yandex Maps source is temporarily unavailable.'
    ) {
        parent::__construct(
            message: $message,
            errorCode: 'YANDEX_MAPS_UNAVAILABLE',
        );
    }
}
