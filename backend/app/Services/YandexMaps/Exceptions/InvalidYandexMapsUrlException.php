<?php

namespace App\Services\YandexMaps\Exceptions;

class InvalidYandexMapsUrlException extends YandexMapsParserException
{
    public function __construct()
    {
        parent::__construct(
            message: 'Invalid Yandex Maps organization URL.',
            errorCode: 'INVALID_YANDEX_MAPS_URL',
        );
    }
}
