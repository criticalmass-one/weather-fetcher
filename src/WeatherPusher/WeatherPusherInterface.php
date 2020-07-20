<?php declare(strict_types=1);

namespace App\WeatherPusher;

use App\Entity\Weather;

interface WeatherPusherInterface
{
    public function pushWeather(Weather $weather): bool;
}
