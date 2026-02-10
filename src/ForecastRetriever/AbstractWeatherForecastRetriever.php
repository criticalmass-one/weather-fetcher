<?php declare(strict_types=1);

namespace App\ForecastRetriever;

use Cmfcmf\OpenWeatherMap;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\Psr18Client;

abstract class AbstractWeatherForecastRetriever implements WeatherForecastRetrieverInterface
{
    protected OpenWeatherMap $openWeatherMap;

    public function __construct(
        protected readonly LoggerInterface $logger,
        string $owmApiKey
    ) {
        $psr18Client = new Psr18Client();

        $this->openWeatherMap = new OpenWeatherMap($owmApiKey, $psr18Client, $psr18Client);
    }
}
