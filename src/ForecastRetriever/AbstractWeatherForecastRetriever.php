<?php declare(strict_types=1);

namespace App\ForecastRetriever;

use Cmfcmf\OpenWeatherMap;
use Http\Adapter\Guzzle6\Client;
use Http\Factory\Guzzle\RequestFactory;
use Psr\Log\LoggerInterface;

abstract class AbstractWeatherForecastRetriever implements WeatherForecastRetrieverInterface
{
    protected OpenWeatherMap $openWeatherMap;

    public function __construct(
        protected readonly LoggerInterface $logger,
        string $owmApiKey
    ) {
        $httpRequestFactory = new RequestFactory();
        $httpClient = Client::createWithConfig([]);

        $this->openWeatherMap = new OpenWeatherMap($owmApiKey, $httpClient, $httpRequestFactory);
    }
}
