<?php declare(strict_types=1);

namespace App\Tests;

use App\ForecastRetriever\WeatherForecastRetriever;
use Cmfcmf\OpenWeatherMap\WeatherForecast;
use PHPUnit\Framework\TestCase;

class WeatherForecastRetrieverTest extends TestCase
{
    private function forecast(): WeatherForecast
    {
        return new WeatherForecast(new \SimpleXMLElement(FakeData::forecastXML()), 'Berlin', 2);
    }

    public function testFindsTheForecastOfTheRideDay(): void
    {
        $forecast = WeatherForecastRetriever::findForecastForDay($this->forecast(), date('Y-m-d'));

        $this->assertNotNull($forecast);
        $this->assertSame(date('Y-m-d'), $forecast->time->from->format('Y-m-d'));
    }

    public function testReturnsNothingWhenTheRideDayIsNotInTheForecast(): void
    {
        $this->assertNull(WeatherForecastRetriever::findForecastForDay($this->forecast(), '2030-01-01'));
    }
}
