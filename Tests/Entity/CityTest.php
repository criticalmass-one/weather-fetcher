<?php declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\City;
use App\Entity\CitySlug;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class CityTest extends TestCase
{
    #[Test]
    public function idAndNameAreNullByDefault(): void
    {
        $city = new City();

        self::assertNull($city->getId());
        self::assertNull($city->getName());
        self::assertNull($city->getMainSlug());
    }

    #[Test]
    public function nameAndMainSlugRoundTrip(): void
    {
        $slug = (new CitySlug())->setSlug('hamburg');
        $city = (new City())->setName('Hamburg')->setMainSlug($slug);

        self::assertSame('Hamburg', $city->getName());
        self::assertSame($slug, $city->getMainSlug());
        self::assertSame('hamburg', $city->getMainSlug()->getSlug());
    }

    #[Test]
    public function settersAreFluent(): void
    {
        $city = new City();

        self::assertSame($city, $city->setName('x'));
        self::assertSame($city, $city->setMainSlug(new CitySlug()));
    }

    #[Test]
    public function mainSlugIsNullByDefault(): void
    {
        self::assertNull((new City())->getMainSlug());
    }
}
