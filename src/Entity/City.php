<?php declare(strict_types=1);

namespace App\Entity;

use JMS\Serializer\Annotation as JMS;

/**
 * @JMS\ExclusionPolicy("all")
 */
class City
{
    /**
     * @JMS\Expose
     */
    protected ?int $id = null;

    /**
     * @JMS\Expose
     */
    protected ?CitySlug $mainSlug = null;

    public function getMainSlug(): CitySlug
    {
        return $this->mainSlug;
    }

    public function setMainSlug(CitySlug $citySlug): City
    {
        $this->mainSlug = $citySlug;

        return $this;
    }
}
