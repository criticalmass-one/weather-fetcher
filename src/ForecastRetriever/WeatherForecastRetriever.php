<?php declare(strict_types=1);

namespace App\ForecastRetriever;

use App\Entity\Ride;
use App\Entity\Weather;
use Cmfcmf\OpenWeatherMap\Forecast;
use Cmfcmf\OpenWeatherMap\WeatherForecast;
use Cmfcmf\OpenWeatherMap\Exception as OWMException;

class WeatherForecastRetriever extends AbstractWeatherForecastRetriever
{
    protected function retrieveWeather(CoordInterface $coord): ?Weather
    {
        try {
            /** @var WeatherForecast $owmWeatherForecast */
            $owmWeatherForecast = $this->openWeatherMap->getWeatherForecast($this->getLatLng($ride), 'metric', 'de',
                null, 7);

            /** @var Forecast $owmWeather */
            while ($owmWeather = $owmWeatherForecast->current()) {
                if ($owmWeather->time->from->format('Y-m-d') == $ride->getDateTime()->format('Y-m-d')) {
                    break;
                }

                $owmWeatherForecast->next();
            }

            if ($owmWeather) {
                $weather = $this->createWeatherEntity($owmWeather);
                $weather->setRide($ride);

                $this->doctrine->getManager()->persist($weather);

                return $weather;
            }
        } catch (OWMException $e) {
            $this->logger->alert(sprintf('Cannot retrieve weather data: %s (Code %s).', $e->getMessage(),
                $e->getCode()));
        } catch (\Exception $e) {
            $this->logger->alert(sprintf('Cannot retrieve weather data: %s (Code %s).', $e->getMessage(),
                $e->getCode()));
        }

        return null;
    }
}
