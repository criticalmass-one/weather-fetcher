<?php declare(strict_types=1);

namespace Caldera\WeatherBundle\WeatherFactory;

use Caldera\WeatherBundle\EntityInterface\WeatherInterface;
use Cmfcmf\OpenWeatherMap\Forecast;

interface WeatherFactoryInterface
{
    public function createWeather(Forecast $owmWeather): WeatherInterface;
}