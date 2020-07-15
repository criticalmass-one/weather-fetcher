<?php declare(strict_types=1);

namespace App\ForecastRetriever;

use App\Entity\Ride;
use App\WeatherFactory\WeatherFactoryInterface;
use Cmfcmf\OpenWeatherMap;
use Http\Adapter\Guzzle6\Client;
use Http\Factory\Guzzle\RequestFactory;
use Psr\Log\LoggerInterface;

abstract class AbstractWeatherForecastRetriever implements WeatherForecastRetrieverInterface
{
    protected OpenWeatherMap $openWeatherMap;

    protected LoggerInterface $logger;

    public function __construct(LoggerInterface $logger, string $owmApiKey)
    {
        $this->logger = $logger;

        $httpRequestFactory = new RequestFactory();
        $httpClient = Client::createWithConfig([]);

        $this->openWeatherMap = new OpenWeatherMap($owmApiKey, $httpClient, $httpRequestFactory);
    }
}
