<?php declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\CitySlug;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class CitySlugTest extends TestCase
{
    #[Test]
    public function slugRoundTrip(): void
    {
        $citySlug = new CitySlug();

        self::assertSame($citySlug, $citySlug->setSlug('hamburg'));
        self::assertSame('hamburg', $citySlug->getSlug());
    }

    /**
     * setSlug() advertises a nullable parameter, but the backing property is a non-nullable
     * string, so passing null (or omitting the argument) fails at runtime.
     */
    #[Test]
    public function settingNullSlugThrowsTypeError(): void
    {
        $this->expectException(\TypeError::class);

        (new CitySlug())->setSlug(null);
    }

    #[Test]
    public function readingSlugBeforeItIsSetFails(): void
    {
        $this->expectException(\Error::class);

        (new CitySlug())->getSlug();
    }
}
