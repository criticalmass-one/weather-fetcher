<?php declare(strict_types=1);

namespace App\Tests\Fixture;

use App\Entity\City;
use App\Entity\CitySlug;
use App\Entity\Ride;
use App\Entity\Weather;
use Cmfcmf\OpenWeatherMap\Forecast;
use Cmfcmf\OpenWeatherMap\WeatherForecast;

/**
 * Deterministic builders for entities and OpenWeatherMap payloads used across the suite.
 */
final class Fixtures
{
    public static function ride(
        string $citySlug = 'hamburg',
        string $cityName = 'Hamburg',
        string $dateTime = '2026-06-26 19:00:00',
        ?float $latitude = 53.55,
        ?float $longitude = 9.99,
        string $title = 'Critical Mass Hamburg',
        ?string $location = 'Rathausmarkt',
    ): Ride {
        $city = (new City())
            ->setName($cityName)
            ->setMainSlug((new CitySlug())->setSlug($citySlug));

        $ride = (new Ride())
            ->setTitle($title)
            ->setCity($city)
            ->setDateTime(new \DateTime($dateTime, new \DateTimeZone('Europe/Berlin')))
            ->setLatitude($latitude)
            ->setLongitude($longitude);

        if (null !== $location) {
            $ride->setLocation($location);
        }

        return $ride;
    }

    public static function weather(?Ride $ride = null, string $weatherDateTime = '2026-06-26 00:00:00'): Weather
    {
        return (new Weather())
            ->setRide($ride ?? self::ride())
            ->setWeatherDateTime(new \DateTime($weatherDateTime, new \DateTimeZone('UTC')))
            ->setCreationDateTime(new \DateTime('2026-06-20 12:00:00', new \DateTimeZone('UTC')))
            ->setTemperatureMin(12.5)
            ->setTemperatureMax(21.0)
            ->setWeatherCode(500)
            ->setWeather('light rain')
            ->setWeatherDescription('light rain')
            ->setWeatherIcon('10d');
    }

    /**
     * JSON as returned by criticalmass.in /api/ride?extended=1 (snake_case, unix timestamps).
     *
     * @param array<int, array<string, mixed>> $rides
     */
    public static function rideListJson(array $rides): string
    {
        return json_encode(array_map(static fn (array $ride): array => $ride + [
            'id' => 1,
            'title' => 'Critical Mass',
            'date_time' => 1782493200, // 2026-06-26 19:00:00 Europe/Berlin (17:00 UTC)
            'location' => 'Somewhere',
            'latitude' => 53.55,
            'longitude' => 9.99,
            'city' => [
                'id' => 1,
                'name' => 'Hamburg',
                'main_slug' => ['id' => 7, 'slug' => 'hamburg'],
                'timezone' => 'Europe/Berlin',
            ],
        ], $rides), JSON_THROW_ON_ERROR);
    }

    /**
     * Builds an OpenWeatherMap "daily forecast" XML document with one <time> element per day.
     *
     * @param array<int, array<string, mixed>> $days each item: ['day' => 'Y-m-d', ...optional overrides]
     */
    public static function forecastXml(array $days): string
    {
        $times = '';

        foreach ($days as $day) {
            $day += [
                'day' => '2026-06-26',
                'symbolNumber' => 500,
                'symbolName' => 'light rain',
                'symbolVar' => '10d',
                'precipitation' => 0.25,
                'windDeg' => 315,
                'windSpeed' => 4.38,
                'tempDay' => 20.5,
                'tempMin' => 12.5,
                'tempMax' => 21.0,
                'tempNight' => 13.0,
                'tempEve' => 18.0,
                'tempMorn' => 14.0,
                'pressure' => 1013.25,
                'humidity' => 70,
                'clouds' => 92,
                'withWindDirection' => true,
            ];

            $windDirection = $day['withWindDirection']
                ? sprintf('<windDirection deg="%s" code="NW" name="Northwest"></windDirection>', $day['windDeg'])
                : '';

            $times .= sprintf(
                '<time day="%s">
                    <symbol number="%s" name="%s" var="%s"></symbol>
                    <precipitation value="%s" type="rain"></precipitation>
                    %s
                    <windSpeed mps="%s" name="Gentle Breeze"></windSpeed>
                    <temperature day="%s" min="%s" max="%s" night="%s" eve="%s" morn="%s"></temperature>
                    <pressure unit="hPa" value="%s"></pressure>
                    <humidity value="%s" unit="%%"></humidity>
                    <clouds value="overcast clouds" all="%s" unit="%%"></clouds>
                </time>',
                $day['day'],
                $day['symbolNumber'],
                $day['symbolName'],
                $day['symbolVar'],
                $day['precipitation'],
                $windDirection,
                $day['windSpeed'],
                $day['tempDay'],
                $day['tempMin'],
                $day['tempMax'],
                $day['tempNight'],
                $day['tempEve'],
                $day['tempMorn'],
                $day['pressure'],
                $day['humidity'],
                $day['clouds'],
            );
        }

        return sprintf(
            '<weatherdata>
                <location>
                    <name>Hamburg</name>
                    <type></type>
                    <country>DE</country>
                    <timezone></timezone>
                    <location altitude="0" latitude="53.55" longitude="9.99" geobase="geonames" geobaseid="2911298"></location>
                </location>
                <credit></credit>
                <meta>
                    <lastupdate></lastupdate>
                    <calctime>0.0215</calctime>
                    <nextupdate></nextupdate>
                </meta>
                <sun rise="2026-06-26T03:00:00" set="2026-06-26T19:50:00"></sun>
                <forecast>%s</forecast>
            </weatherdata>',
            $times,
        );
    }

    /**
     * @param array<int, array<string, mixed>> $days
     */
    public static function forecast(array $days, string $units = 'metric'): Forecast
    {
        $weatherForecast = new WeatherForecast(new \SimpleXMLElement(self::forecastXml($days)), $units, 5);
        $weatherForecast->rewind();

        return $weatherForecast->current();
    }

    public static function setPrivateProperty(object $object, string $property, mixed $value): void
    {
        $reflection = new \ReflectionProperty($object, $property);
        $reflection->setValue($object, $value);
    }
}
