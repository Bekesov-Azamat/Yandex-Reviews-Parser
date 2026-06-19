<?php

namespace App\Providers;

use App\Services\YandexMaps\FakeYandexMapsParser;
use App\Services\YandexMaps\RealYandexMapsParser;
use App\Services\YandexMaps\YandexMapsParserInterface;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(YandexMapsParserInterface::class, function () {
            return match (config('services.yandex_maps.parser', 'real')) {
                'fake' => app(FakeYandexMapsParser::class),
                default => app(RealYandexMapsParser::class),
            };
        });
    }

    public function boot(): void {}
}
