<?php declare(strict_types=1);

namespace App\Tests\WeatherFactory;

use App\Entity\Ride;
use App\Tests\FakeData;
use App\Tests\Fixture\Fixtures;
use App\WeatherFactory\WeatherFactory;
use Cmfcmf\OpenWeatherMap\WeatherForecast;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class WeatherFactoryTest extends TestCase
{
    #[Test]
    public function mapsAllMeasurementsFromLegacyFixture(): void
    {
        $forecast = new WeatherForecast(new \SimpleXMLElement(FakeData::forecastXML()), 'Berlin', 2);
        $ride = new Ride();

        $weather = WeatherFactory::createWeather($forecast->current(), $ride);

        self::assertEquals(40.59, $weather->getTemperatureMin());
        self::assertEquals(41.0, $weather->getTemperatureMax());
        self::assertEquals(41.0, $weather->getTemperatureMorning());
        self::assertEquals(41.0, $weather->getTemperatureDay());
        self::assertEquals(41.0, $weather->getTemperatureEvening());
        self::assertEquals(40.59, $weather->getTemperatureNight());

        self::assertEquals(1048.25, $weather->getPressure());
        self::assertEquals(97.0, $weather->getHumidity());
        self::assertEquals(500, $weather->getWeatherCode());
        self::assertEquals('light rain', $weather->getWeatherDescription());
        self::assertEquals('10d', $weather->getWeatherIcon());
        self::assertEquals(4.38, $weather->getWindSpeed());
        self::assertEquals(315.0, $weather->getWindDirection());
        self::assertEquals(92.0, $weather->getClouds());
        self::assertEquals(0.25, $weather->getPrecipitation());
    }

    #[Test]
    public function mapsTemperaturesAsFloats(): void
    {
        $forecast = Fixtures::forecast([[
            'tempMin' => 12.5, 'tempMax' => 21, 'tempDay' => 20.5, 'tempMorn' => 14, 'tempEve' => 18, 'tempNight' => 13,
        ]]);

        $weather = WeatherFactory::createWeather($forecast, Fixtures::ride());

        self::assertSame(12.5, $weather->getTemperatureMin());
        self::assertSame(21.0, $weather->getTemperatureMax());
        self::assertSame(20.5, $weather->getTemperatureDay());
        self::assertSame(14.0, $weather->getTemperatureMorning());
        self::assertSame(18.0, $weather->getTemperatureEvening());
        self::assertSame(13.0, $weather->getTemperatureNight());
    }

    #[Test]
    public function weatherDateTimeIsTheStartOfTheForecastDayInUtc(): void
    {
        $weather = WeatherFactory::createWeather(Fixtures::forecast([['day' => '2026-06-27']]), Fixtures::ride());

        self::assertSame('2026-06-27T00:00:00+00:00', $weather->getWeatherDateTime()->format(\DateTimeInterface::ATOM));
    }

    #[Test]
    public function weatherCodeIconAndDescriptionComeFromTheSymbolElement(): void
    {
        $forecast = Fixtures::forecast([['symbolNumber' => 800, 'symbolName' => 'clear sky', 'symbolVar' => '01d']]);

        $weather = WeatherFactory::createWeather($forecast, Fixtures::ride());

        self::assertSame(800, $weather->getWeatherCode());
        self::assertSame('clear sky', $weather->getWeatherDescription());
        self::assertSame('01d', $weather->getWeatherIcon());
    }

    /**
     * "weather" and "weather_description" carry the same value: the OWM XML has no
     * separate "main" category, so the factory copies the description into both.
     */
    #[Test]
    public function weatherEqualsWeatherDescription(): void
    {
        $weather = WeatherFactory::createWeather(Fixtures::forecast([['symbolName' => 'moderate rain']]), Fixtures::ride());

        self::assertSame('moderate rain', $weather->getWeather());
        self::assertSame($weather->getWeatherDescription(), $weather->getWeather());
    }

    #[Test]
    public function mapsWindPressureHumidityCloudsAndPrecipitation(): void
    {
        $forecast = Fixtures::forecast([[
            'windSpeed' => 6.2, 'windDeg' => 253, 'pressure' => 1001.5, 'humidity' => 55, 'clouds' => 10, 'precipitation' => 3.75,
        ]]);

        $weather = WeatherFactory::createWeather($forecast, Fixtures::ride());

        self::assertSame(6.2, $weather->getWindSpeed());
        self::assertSame(253.0, $weather->getWindDirection());
        self::assertSame(1001.5, $weather->getPressure());
        self::assertSame(55.0, $weather->getHumidity());
        self::assertSame(10.0, $weather->getClouds());
        self::assertSame(3.75, $weather->getPrecipitation());
    }

    #[Test]
    public function missingWindDirectionStaysNull(): void
    {
        $weather = WeatherFactory::createWeather(Fixtures::forecast([['withWindDirection' => false]]), Fixtures::ride());

        self::assertNull($weather->getWindDirection());
        self::assertSame(4.38, $weather->getWindSpeed());
    }

    #[Test]
    public function assignsTheGivenRide(): void
    {
        $ride = Fixtures::ride();

        $weather = WeatherFactory::createWeather(Fixtures::forecast([[]]), $ride);

        self::assertSame($ride, $weather->getRide());
    }

    #[Test]
    public function storesTheRawForecastAsJson(): void
    {
        $forecast = Fixtures::forecast([['symbolNumber' => 802]]);

        $weather = WeatherFactory::createWeather($forecast, Fixtures::ride());

        self::assertJson($weather->getJson());
        self::assertSame(json_encode($forecast), $weather->getJson());

        $decoded = json_decode($weather->getJson(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(802, $decoded['weather']['id']);
        self::assertSame(12.5, $decoded['temperature']['min']['value']);
        self::assertSame('Hamburg', $decoded['city']['name']);
    }

    #[Test]
    public function setsAFreshCreationDateTime(): void
    {
        $before = new \DateTime();
        $weather = WeatherFactory::createWeather(Fixtures::forecast([[]]), Fixtures::ride());
        $after = new \DateTime();

        self::assertGreaterThanOrEqual($before, $weather->getCreationDateTime());
        self::assertLessThanOrEqual($after, $weather->getCreationDateTime());
    }

    #[Test]
    public function createsIndependentEntitiesPerCall(): void
    {
        $forecast = Fixtures::forecast([[]]);

        $first = WeatherFactory::createWeather($forecast, Fixtures::ride());
        $second = WeatherFactory::createWeather($forecast, Fixtures::ride());

        self::assertNotSame($first, $second);
        self::assertNotSame($first->getRide(), $second->getRide());
    }

    #[Test]
    public function imperialUnitsAreCopiedVerbatimWithoutConversion(): void
    {
        $forecast = Fixtures::forecast([['tempMin' => 50.0, 'windSpeed' => 10.0]], 'imperial');

        $weather = WeatherFactory::createWeather($forecast, Fixtures::ride());

        self::assertSame(50.0, $weather->getTemperatureMin());
        self::assertSame(10.0, $weather->getWindSpeed());
    }

    #[Test]
    public function cannotBeInstantiated(): void
    {
        $constructor = new \ReflectionMethod(WeatherFactory::class, '__construct');

        self::assertTrue($constructor->isPrivate());
    }
}
