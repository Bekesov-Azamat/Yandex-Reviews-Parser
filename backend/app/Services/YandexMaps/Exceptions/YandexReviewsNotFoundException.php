<?php

namespace App\Services\YandexMaps\Exceptions;

class YandexReviewsNotFoundException extends YandexMapsParserException
{
    public function __construct()
    {
        parent::__construct(
            message: 'Reviews were not found for this organization.',
            errorCode: 'YANDEX_REVIEWS_NOT_FOUND',
        );
    }
}
