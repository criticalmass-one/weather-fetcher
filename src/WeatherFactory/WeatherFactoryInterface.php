<?php declare(strict_types=1);

namespace App\WeatherFactory;

use App\Entity\Weather;
use Cmfcmf\OpenWeatherMap\Forecast;

interface WeatherFactoryInterface
{
    public function createWeather(Forecast $owmWeather): Weather;
}