<?php declare(strict_types=1);

namespace App\Controller\Api;

use App\ForecastRetriever\WeatherForecastRetrieverInterface;
use App\RideRetriever\RideRetrieverInterface;
use App\WeatherPusher\WeatherPusherInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class WeatherApiController extends AbstractController
{
    #[Route('/api/weather/{citySlug}/{date}', name: 'api_weather_fetch', methods: ['POST'])]
    public function fetch(
        string $citySlug,
        string $date,
        RideRetrieverInterface $rideRetriever,
        WeatherForecastRetrieverInterface $forecastRetriever,
        WeatherPusherInterface $weatherPusher,
    ): JsonResponse {
        $dateTime = new \DateTime($date);
        $untilDateTime = (clone $dateTime)->modify('+1 day');

        $rides = $rideRetriever->retrieveRides($dateTime, $untilDateTime);

        $matchingRide = null;

        foreach ($rides as $ride) {
            if ($ride->getCity()->getMainSlug()->getSlug() === $citySlug) {
                $matchingRide = $ride;
                break;
            }
        }

        if (!$matchingRide) {
            return $this->json(['error' => 'Ride not found'], Response::HTTP_NOT_FOUND);
        }

        $weatherList = $forecastRetriever->retrieveWeatherForecastsForRideList([$matchingRide]);

        if (empty($weatherList)) {
            return $this->json(['error' => 'Could not retrieve weather data'], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        $weather = $weatherList[0];
        $pushed = $weatherPusher->pushWeather($weather);

        return $this->json([
            'success' => true,
            'pushed' => $pushed,
            'weather' => [
                'temperatureMin' => $weather->getTemperatureMin(),
                'temperatureMax' => $weather->getTemperatureMax(),
                'temperatureMorning' => $weather->getTemperatureMorning(),
                'temperatureDay' => $weather->getTemperatureDay(),
                'temperatureEvening' => $weather->getTemperatureEvening(),
                'temperatureNight' => $weather->getTemperatureNight(),
                'pressure' => $weather->getPressure(),
                'humidity' => $weather->getHumidity(),
                'weather' => $weather->getWeather(),
                'weatherDescription' => $weather->getWeatherDescription(),
                'weatherIcon' => $weather->getWeatherIcon(),
                'windSpeed' => $weather->getWindSpeed(),
                'windDirection' => $weather->getWindDirection(),
                'clouds' => $weather->getClouds(),
                'precipitation' => $weather->getPrecipitation(),
            ],
        ]);
    }
}
