<?php declare(strict_types=1);

namespace App\WeatherFactory;

use App\Entity\Weather;
use Cmfcmf\OpenWeatherMap\Forecast;

class WeatherFactory implements WeatherFactoryInterface
{
    private function __construct()
    {
        
    }

    public static function createWeather(Forecast $owmWeather): Weather
    {
        $weather = static::createEntity();

        $weather = static::assignProperties($weather, $owmWeather);

        $weather->setCreationDateTime(new \DateTime());

        return $weather;
    }

    protected static function createEntity(): Weather
    {
        return new Weather();
    }

    protected static function getBaseMapping(): array
    {
        return [
            'setWeatherDateTime' => ['time', 'from'],
            'setWeatherCode' => ['weather', 'id'],
        ];
    }

    protected static function createMapping(Weather $weather): array
    {
        $reflection = new \ReflectionClass($weather);

        $mapping = static::getBaseMapping();

        foreach ($reflection->getMethods() as $method) {
            $methodName = $method->getShortName();

            if (array_key_exists($methodName, $mapping)) {
                continue;
            }

            if (0 !== strpos($methodName, 'set')) {
                continue;
            }

            preg_match_all('/([A-Z][a-z]+)/', $methodName, $matches);

            $path = array_map('strtolower', $matches[0]);

            $mapping[$methodName] = $path;
        }

        return $mapping;
    }

    protected static function assignProperties(Weather $weather, Forecast $owmWeather): Weather
    {
        $mapping = static::createMapping($weather);

        foreach ($mapping as $methodName => $path) {
            $weather = static::assignProperty($weather, $owmWeather, $methodName, $path);
        }

        return $weather;
    }

    protected static function assignProperty(Weather $weather, Forecast $owmWeather, string $methodName, array $path): Weather
    {
        if (2 === count($path)) {
            list($prop1, $prop2) = $path;

            if (property_exists($owmWeather, $prop1) && property_exists($owmWeather->{$prop1}, $prop2)) {
                if (is_object($owmWeather->{$prop1}->{$prop2}) && method_exists($owmWeather->{$prop1}->{$prop2}, 'getValue')) {
                    $weather->$methodName($owmWeather->{$prop1}->{$prop2}->getValue());
                } elseif (is_string($owmWeather->{$prop1}->{$prop2}) || is_int($owmWeather->{$prop1}->{$prop2}) || is_float($owmWeather->{$prop1}->{$prop2})) {
                    $weather->$methodName($owmWeather->{$prop1}->{$prop2});
                } elseif ($owmWeather->{$prop1}->{$prop2} instanceof \DateTimeInterface) {
                    $weather->$methodName($owmWeather->{$prop1}->{$prop2});
                }
            }
        }

        if (1 === count($path)) {
            list($prop1) = $path;

            if (property_exists($owmWeather, $prop1)) {
                if (is_object($owmWeather->{$prop1}) && method_exists($owmWeather->{$prop1}, 'getValue')) {
                    $weather->$methodName($owmWeather->{$prop1}->getValue());
                } elseif (is_string($owmWeather->{$prop1}) || is_int($owmWeather->{$prop1})) {
                    $weather->$methodName($owmWeather->{$prop1});
                } elseif ($owmWeather->{$prop1} instanceof \Cmfcmf\OpenWeatherMap\Util\Weather) {
                    $weather
                        ->setWeatherCode($owmWeather->{$prop1}->id)
                        ->setWeather($owmWeather->{$prop1}->description)
                        ->setWeatherIcon($owmWeather->{$prop1}->icon);
                }
            }
        }

        return $weather;
    }
}
