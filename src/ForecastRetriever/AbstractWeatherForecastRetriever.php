<?php declare(strict_types=1);

namespace App\ForecastRetriever;

use App\WeatherFactory\WeatherFactoryInterface;
use Cmfcmf\OpenWeatherMap;
use Http\Adapter\Guzzle6\Client;
use Http\Factory\Guzzle\RequestFactory;
use Psr\Log\LoggerInterface;

abstract class AbstractWeatherForecastRetriever implements WeatherForecastRetrieverInterface
{
    /** @var OpenWeatherMap openWeatherMap */
    protected $openWeatherMap;

    /** @var array $newWeatherList */
    protected $newWeatherList = [];

    /** @var LoggerInterface $logger */
    protected $logger;

    /** @var WeatherFactoryInterface $weatherFactory */
    protected $weatherFactory;

    public function __construct(WeatherFactoryInterface $weatherFactory, LoggerInterface $logger, string $owmApiKey)
    {
        $this->logger = $logger;
        $this->weatherFactory = $weatherFactory;

        $httpRequestFactory = new RequestFactory();
        $httpClient = Client::createWithConfig([]);

        $this->openWeatherMap = new OpenWeatherMap($owmApiKey, $httpClient, $httpRequestFactory);
    }

    protected function getLatLng(Ride $ride): array
    {
        if ($ride->getHasLocation() && $ride->getCoord()) {
            $ride->getCoord()->toLatLonArray();
        }

        return $ride->getCity()->getCoord()->toLatLonArray();
    }

    public function getNewWeatherForecasts(): array
    {
        return $this->newWeatherList;
    }
}
