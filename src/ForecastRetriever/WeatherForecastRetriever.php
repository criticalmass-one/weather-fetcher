<?php declare(strict_types=1);

namespace App\ForecastRetriever;

use App\Entity\Ride;
use App\Entity\Weather;
use App\WeatherFactory\WeatherFactory;
use Cmfcmf\OpenWeatherMap\Forecast;
use Cmfcmf\OpenWeatherMap\WeatherForecast;

class WeatherForecastRetriever extends AbstractWeatherForecastRetriever
{
    protected function getCoord(Ride $ride): ?array
    {
        if (!$ride->getLatitude() || !$ride->getLongitude()) {
            return null;
        }

        return [
            'lat' => $ride->getLatitude(),
            'lon' => $ride->getLongitude(),
        ];
    }

    protected function retrieveWeather(Ride $ride): ?Weather
    {
        try {
            $coord = $this->getCoord($ride);

            if (!$coord) {
                return null;
            }

            /** @var WeatherForecast $owmWeatherForecast */
            $owmWeatherForecast = $this->openWeatherMap->getDailyWeatherForecast($coord, 'metric', 'de', null, 5);

            $owmWeatherForecast->rewind();

            /** @var Forecast $owmWeather */
            while ($owmWeatherForecast->valid() && $owmWeather = $owmWeatherForecast->current()) {
                if ($owmWeather->time->from->format('Y-m-d') === $ride->getDateTime()->format('Y-m-d')) {
                    break;
                }

                $owmWeatherForecast->next();
            }

            if ($owmWeather) {
                return WeatherFactory::createWeather($owmWeather, $ride);
            }
        } catch (\Exception $e) {
            $this->logger->alert(sprintf('Cannot retrieve weather data: %s (Code %s).', $e->getMessage(),
                $e->getCode()));
        }

        return null;
    }

    public function retrieveWeatherForecastsForRideList(array $rideList = []): array
    {
        $weatherForecastList = [];

        foreach ($rideList as $ride) {
            $weather = $this->retrieveWeather($ride);

            if ($weather) {
                $weatherForecastList[] = $weather;
            }
        }

        return $weatherForecastList;
    }
}
