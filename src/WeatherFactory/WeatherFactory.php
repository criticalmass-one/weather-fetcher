<?php declare(strict_types=1);

namespace App\WeatherFactory;

use App\Entity\Weather;
use Cmfcmf\OpenWeatherMap\Forecast;

class WeatherFactory implements WeatherFactoryInterface
{
    /** @var array $mapping */
    protected $mapping = [
        'setWeatherDateTime' => ['time', 'from'],
        'setWeatherCode' => ['weather', 'id'],
    ];

    protected function getMapping(Weather $weather): array
    {
        $reflection = new \ReflectionClass($weather);

        foreach ($reflection->getMethods() as $method) {
            $methodName = $method->getShortName();

            if (array_key_exists($methodName, $this->mapping)) {
                continue;
            }

            if (0 !== strpos($methodName, 'set')) {
                continue;
            }

            preg_match_all('/([A-Z][a-z]+)/', $methodName, $matches);

            $path = array_map('strtolower', $matches[0]);

            $this->mapping[$methodName] = $path;
        }

        return $this->mapping;
    }

    protected function assignProperties(Weather $weather, Forecast $owmWeather): Weather
    {
        foreach ($this->getMapping($weather) as $methodName => $path) {
            $weather = $this->assignProperty($weather, $owmWeather, $methodName, $path);
        }

        return $weather;
    }

    protected function assignProperty(Weather $weather, Forecast $owmWeather, string $methodName, array $path): Weather
    {
        if (2 === count($path)) {
            list($prop1, $prop2) = $path;

            if (property_exists($owmWeather, $prop1) && property_exists($owmWeather->{$prop1}, $prop2)) {
                if (is_object($owmWeather->{$prop1}->{$prop2}) && method_exists($owmWeather->{$prop1}->{$prop2}, 'getValue')) {
                    $weather->$methodName($owmWeather->{$prop1}->{$prop2}->getValue());
                }

                if (is_string($owmWeather->{$prop1}->{$prop2}) || is_int($owmWeather->{$prop1}->{$prop2})) {
                    $weather->$methodName($owmWeather->{$prop1}->{$prop2});
                }
            }
        }

        if (1 === count($path)) {
            list($prop1) = $path;

            if (property_exists($owmWeather, $prop1)) {
                if (is_object($owmWeather->{$prop1}) && method_exists($owmWeather->{$prop1}, 'getValue')) {
                    $weather->$methodName($owmWeather->{$prop1}->getValue());
                }

                if (is_string($owmWeather->{$prop1}) || is_int($owmWeather->{$prop1})) {
                    $weather->$methodName($owmWeather->{$prop1});
                }
            }
        }

        return $weather;
    }

    public function createWeather(Forecast $owmWeather): Weather
    {
        $weather = $this->createEntity();

        $weather = $this->assignProperties($weather, $owmWeather);

        $weather->setCreationDateTime(new \DateTime());

        return $weather;
    }
}