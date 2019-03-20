<?php declare(strict_types=1);

namespace Caldera\WeatherBundle\ForecastRetriever;

interface WeatherForecastRetrieverInterface
{
    public function retrieve(\DateTime $startDateTime = null, \DateTime $endDateTime = null): array;
    public function getNewWeatherForecasts(): array;
}
