<?php declare(strict_types=1);

namespace App\Entity;

class Ride
{
    private int $id;
    private City $city;
    private string $title;
    private \DateTime $dateTime;
    private ?string $location = null;
    private ?float $latitude = null;
    private ?float $longitude = null;

    public function setTitle(string $title): Ride
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setCity(City $city): Ride
    {
        $this->city = $city;

        return $this;
    }

    public function getCity(): City
    {
        return $this->city;
    }

    public function setDateTime(\DateTime $dateTime): Ride
    {
        $this->dateTime = $dateTime;

        return $this;
    }

    public function getDateTime(): \DateTime
    {
        return $this->dateTime;
    }

    public function hasLocation(): bool
    {
        return $this->location !== null;
    }

    public function setLocation(string $location): Ride
    {
        $this->location = $location;

        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLatitude(?float $latitude): Ride
    {
        $this->latitude = $latitude;

        return $this;
    }

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function setLongitude(?float $longitude): Ride
    {
        $this->longitude = $longitude;

        return $this;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }
}
