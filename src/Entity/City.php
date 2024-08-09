<?php declare(strict_types=1);

namespace App\Entity;

class City
{
    protected ?int $id = null;

    protected ?string $name = null;

    protected ?CitySlug $mainSlug = null;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): City
    {
        $this->name = $name;

        return $this;
    }

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
