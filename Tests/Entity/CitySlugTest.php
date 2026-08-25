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

    #[Test]
    public function slugIsRequiredAndNotNullable(): void
    {
        $parameter = new \ReflectionMethod(CitySlug::class, 'setSlug')->getParameters()[0];

        self::assertFalse($parameter->isOptional());
        self::assertFalse($parameter->allowsNull());
        self::assertSame('string', (string) $parameter->getType());
    }

    #[Test]
    public function readingSlugBeforeItIsSetFails(): void
    {
        $this->expectException(\Error::class);

        (new CitySlug())->getSlug();
    }
}
