<?php declare(strict_types=1);

namespace App\Entity;

use JMS\Serializer\Annotation as JMS;

class CitySlug
{
    private int $id;
    private string $slug;

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug = null): CitySlug
    {
        $this->slug = $slug;

        return $this;
    }
}
