<?php declare(strict_types=1);

namespace App\Entity;

class City
{
    private ?int $id = null;
    private ?string $name = null;
    private ?CitySlug $mainSlug = null;

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

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }
}
