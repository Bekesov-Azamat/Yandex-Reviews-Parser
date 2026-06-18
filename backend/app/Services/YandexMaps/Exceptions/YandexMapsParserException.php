<?php

namespace App\Services\YandexMaps\Exceptions;

use RuntimeException;

class YandexMapsParserException extends RuntimeException
{
    public function __construct(
        string $message = 'Yandex Maps parser failed.',
        private readonly string $errorCode = 'YANDEX_PARSER_FAILED',
        int $code = 0,
    ) {
        parent::__construct($message, $code);
    }

    public function errorCode(): string
    {
        return $this->errorCode;
    }
}
