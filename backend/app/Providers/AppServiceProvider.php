<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\YandexMaps\FakeYandexMapsParser;
use App\Services\YandexMaps\YandexMapsParserInterface;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(YandexMapsParserInterface::class, FakeYandexMapsParser::class);
    }

    public function boot(): void
    {
        //
    }
}
