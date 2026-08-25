<?php declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Ride;
use App\Entity\Weather;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class WeatherTest extends TestCase
{
    #[Test]
    public function creationDateTimeIsInitialisedOnConstruction(): void
    {
        $before = new \DateTime();
        $weather = new Weather();
        $after = new \DateTime();

        self::assertInstanceOf(\DateTime::class, $weather->getCreationDateTime());
        self::assertGreaterThanOrEqual($before, $weather->getCreationDateTime());
        self::assertLessThanOrEqual($after, $weather->getCreationDateTime());
    }

    #[Test]
    public function creationDateTimeCanBeReset(): void
    {
        $weather = (new Weather())->setCreationDateTime(null);

        self::assertNull($weather->getCreationDateTime());
    }

    #[Test]
    public function everythingExceptCreationDateTimeIsNullByDefault(): void
    {
        $weather = new Weather();

        self::assertNull($weather->getJson());
        self::assertNull($weather->getWeatherDateTime());
        self::assertNull($weather->getTemperatureMin());
        self::assertNull($weather->getTemperatureMax());
        self::assertNull($weather->getTemperatureMorning());
        self::assertNull($weather->getTemperatureDay());
        self::assertNull($weather->getTemperatureEvening());
        self::assertNull($weather->getTemperatureNight());
        self::assertNull($weather->getPressure());
        self::assertNull($weather->getHumidity());
        self::assertNull($weather->getWeatherCode());
        self::assertNull($weather->getWeather());
        self::assertNull($weather->getWeatherDescription());
        self::assertNull($weather->getWeatherIcon());
        self::assertNull($weather->getWindSpeed());
        self::assertNull($weather->getWindDirection());
        self::assertNull($weather->getClouds());
        self::assertNull($weather->getPrecipitation());
    }

    /**
     * @return iterable<string, array{string, string, mixed}>
     */
    public static function scalarProperties(): iterable
    {
        yield 'json' => ['setJson', 'getJson', '{"a":1}'];
        yield 'temperatureMin' => ['setTemperatureMin', 'getTemperatureMin', -3.5];
        yield 'temperatureMax' => ['setTemperatureMax', 'getTemperatureMax', 31.0];
        yield 'temperatureMorning' => ['setTemperatureMorning', 'getTemperatureMorning', 10.1];
        yield 'temperatureDay' => ['setTemperatureDay', 'getTemperatureDay', 20.2];
        yield 'temperatureEvening' => ['setTemperatureEvening', 'getTemperatureEvening', 15.3];
        yield 'temperatureNight' => ['setTemperatureNight', 'getTemperatureNight', 8.4];
        yield 'pressure' => ['setPressure', 'getPressure', 1013.25];
        yield 'humidity' => ['setHumidity', 'getHumidity', 70.0];
        yield 'weatherCode' => ['setWeatherCode', 'getWeatherCode', 800];
        yield 'weather' => ['setWeather', 'getWeather', 'Clear'];
        yield 'weatherDescription' => ['setWeatherDescription', 'getWeatherDescription', 'clear sky'];
        yield 'weatherIcon' => ['setWeatherIcon', 'getWeatherIcon', '01d'];
        yield 'windSpeed' => ['setWindSpeed', 'getWindSpeed', 4.38];
        yield 'windDirection' => ['setWindDirection', 'getWindDirection', 315.0];
        yield 'clouds' => ['setClouds', 'getClouds', 92.0];
        yield 'precipitation' => ['setPrecipitation', 'getPrecipitation', 0.25];
    }

    #[Test]
    #[DataProvider('scalarProperties')]
    public function scalarPropertiesRoundTripAndAcceptNull(string $setter, string $getter, mixed $value): void
    {
        $weather = new Weather();

        self::assertSame($weather, $weather->$setter($value));
        self::assertSame($value, $weather->$getter());

        $weather->$setter(null);
        self::assertNull($weather->$getter());

        $weather->$setter();
        self::assertNull($weather->$getter());
    }

    #[Test]
    public function weatherDateTimeRoundTrip(): void
    {
        $dateTime = new \DateTime('2026-06-26 00:00:00', new \DateTimeZone('UTC'));
        $weather = (new Weather())->setWeatherDateTime($dateTime);

        self::assertSame($dateTime, $weather->getWeatherDateTime());

        $weather->setWeatherDateTime(null);
        self::assertNull($weather->getWeatherDateTime());
    }

    #[Test]
    public function rideRoundTrip(): void
    {
        $ride = new Ride();
        $weather = new Weather();

        self::assertSame($weather, $weather->setRide($ride));
        self::assertSame($ride, $weather->getRide());
    }

    #[Test]
    public function readingRideBeforeItIsSetFails(): void
    {
        $this->expectException(\Error::class);

        (new Weather())->getRide();
    }
}
