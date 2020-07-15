<?php declare(strict_types=1);

namespace App\ForecastRetriever;

interface WeatherForecastRetrieverInterface
{
    public function retrieveWeatherForecastsForRideList(array $rideList = []): array;
}
