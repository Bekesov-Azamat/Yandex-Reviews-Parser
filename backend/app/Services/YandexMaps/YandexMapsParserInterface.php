<?php

namespace App\Services\YandexMaps;

use App\Services\YandexMaps\Dto\ParsedOrganizationData;

interface YandexMapsParserInterface
{
    public function parse(string $url, int $reviewsLimit = 600): ParsedOrganizationData;
}
