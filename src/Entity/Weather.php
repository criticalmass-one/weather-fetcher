<?php declare(strict_types=1);

namespace App\Entity;

class Weather
{
    protected int $rideId = null;

    protected ?string $json = null;

    protected ?\DateTime $weatherDateTime = null;

    protected ?\DateTime $creationDateTime = null;

    protected ?float $temperatureMin = null;

    protected ?float $temperatureMax = null;

    protected ?float $temperatureMorning = null;

    protected ?float $temperatureDay = null;

    protected ?float $temperatureEvening = null;

    protected ?float $temperatureNight = null;

    protected ?float $pressure = null;

    protected ?float $humidity = null;

    protected ?int $weatherCode = null;

    protected ?string $weather = null;

    protected ?string $weatherDescription = null;

    protected ?string $weatherIcon = null;

    protected ?float $windSpeed = null;

    protected ?float $windDirection = null;

    protected ?float $clouds = null;

    protected ?float $precipitation = null;

    public function __construct()
    {
        $this->creationDateTime = new \DateTime();
    }

    public function getJson(): ?string
    {
        return $this->json;
    }

    public function setJson(string $json = null): Weather
    {
        $this->json = $json;

        return $this;
    }

    public function getWeatherDateTime(): ?\DateTime
    {
        return $this->weatherDateTime;
    }

    public function setWeatherDateTime(\DateTime $weatherDateTime = null): Weather
    {
        $this->weatherDateTime = $weatherDateTime;

        return $this;
    }

    public function getCreationDateTime(): ?\DateTime
    {
        return $this->creationDateTime;
    }

    public function setCreationDateTime(\DateTime $creationDateTime = null): Weather
    {
        $this->creationDateTime = $creationDateTime;

        return $this;
    }

    public function getTemperatureMin(): ?float
    {
        return $this->temperatureMin;
    }

    public function setTemperatureMin(float $temperatureMin = null): Weather
    {
        $this->temperatureMin = $temperatureMin;

        return $this;
    }

    public function getTemperatureMax(): ?float
    {
        return $this->temperatureMax;
    }

    public function setTemperatureMax(float $temperatureMax = null): Weather
    {
        $this->temperatureMax = $temperatureMax;

        return $this;
    }

    public function getTemperatureMorning(): ?float
    {
        return $this->temperatureMorning;
    }

    public function setTemperatureMorning(float $temperatureMorning = null): Weather
    {
        $this->temperatureMorning = $temperatureMorning;

        return $this;
    }

    public function getTemperatureDay(): ?float
    {
        return $this->temperatureDay;
    }

    public function setTemperatureDay(float $temperatureDay = null): Weather
    {
        $this->temperatureDay = $temperatureDay;

        return $this;
    }

    public function getTemperatureEvening(): ?float
    {
        return $this->temperatureEvening;
    }

    public function setTemperatureEvening(float $temperatureEvening = null): Weather
    {
        $this->temperatureEvening = $temperatureEvening;

        return $this;
    }

    public function getTemperatureNight(): ?float
    {
        return $this->temperatureNight;
    }

    public function setTemperatureNight(float $temperatureNight = null): Weather
    {
        $this->temperatureNight = $temperatureNight;

        return $this;
    }

    public function getPressure(): ?float
    {
        return $this->pressure;
    }

    public function setPressure(float $pressure = null): Weather
    {
        $this->pressure = $pressure;

        return $this;
    }

    public function getHumidity(): ?float
    {
        return $this->humidity;
    }

    public function setHumidity(float $humidity = null): Weather
    {
        $this->humidity = $humidity;

        return $this;
    }

    public function getWeatherCode(): ?int
    {
        return $this->weatherCode;
    }

    public function setWeatherCode(int $weatherCode = null): Weather
    {
        $this->weatherCode = $weatherCode;

        return $this;
    }

    public function getWeather(): ?string
    {
        return $this->weather;
    }

    public function setWeather(string $weather = null): Weather
    {
        $this->weather = $weather;

        return $this;
    }

    public function getWeatherDescription(): ?string
    {
        return $this->weatherDescription;
    }

    public function setWeatherDescription(string $weatherDescription = null): Weather
    {
        $this->weatherDescription = $weatherDescription;

        return $this;
    }

    public function getWindSpeed(): ?float
    {
        return $this->windSpeed;
    }

    public function setWindSpeed(float $windSpeed = null): Weather
    {
        $this->windSpeed = $windSpeed;

        return $this;
    }

    public function getWindDirection(): ?float
    {
        return $this->windDirection;
    }

    public function setWindDirection(float $windDirection = null): Weather
    {
        $this->windDirection = $windDirection;

        return $this;
    }

    public function getClouds(): ?float
    {
        return $this->clouds;
    }

    public function setClouds(float $clouds = null): Weather
    {
        $this->clouds = $clouds;

        return $this;
    }

    public function getPrecipitation(): ?float
    {
        return $this->precipitation;
    }

    public function setPrecipitation(float $precipitation = null): Weather
    {
        $this->precipitation = $precipitation;

        return $this;
    }

    public function getWeatherIcon(): ?string
    {
        return $this->weatherIcon;
    }

    public function setWeatherIcon(string $weatherIcon = null): Weather
    {
        $this->weatherIcon = $weatherIcon;

        return $this;
    }
}
