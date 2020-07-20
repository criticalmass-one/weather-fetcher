<?php declare(strict_types=1);

namespace App\Entity;

use JMS\Serializer\Annotation as JMS;

/**
 * @JMS\ExclusionPolicy("all")
 */
class Ride
{
    /**
     * @JMS\Expose
     */
    protected int $id;

    /**
     * @JMS\Expose
     */
    protected City $city;

    /**
     * @JMS\Expose()
     * @JMS\Type("DateTime<'U'>")
     */
    protected \DateTime $dateTime;

    /**
     * @JMS\Expose
     */
    protected ?float $latitude = null;

    /**
     * @JMS\Expose
     */
    protected ?float $longitude = null;

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
