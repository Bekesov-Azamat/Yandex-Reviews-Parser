<?php

namespace App\Services\YandexMaps\Exceptions;

class YandexMapsUnavailableException extends YandexMapsParserException
{
    public function __construct()
    {
        parent::__construct(
            message: 'Yandex Maps source is temporarily unavailable.',
            errorCode: 'YANDEX_MAPS_UNAVAILABLE',
        );
    }
}
