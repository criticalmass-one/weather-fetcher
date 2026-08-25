<?php declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\City;
use App\Entity\Ride;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class RideTest extends TestCase
{
    #[Test]
    public function hasLocationIsFalseUntilALocationIsSet(): void
    {
        $ride = new Ride();

        self::assertFalse($ride->hasLocation());
        self::assertNull($ride->getLocation());

        $ride->setLocation('Rathausmarkt');

        self::assertTrue($ride->hasLocation());
        self::assertSame('Rathausmarkt', $ride->getLocation());
    }

    #[Test]
    public function emptyStringLocationStillCountsAsLocation(): void
    {
        $ride = (new Ride())->setLocation('');

        self::assertTrue($ride->hasLocation());
    }

    #[Test]
    public function coordinatesDefaultToNullAndCanBeResetToNull(): void
    {
        $ride = new Ride();

        self::assertNull($ride->getLatitude());
        self::assertNull($ride->getLongitude());

        $ride->setLatitude(53.55)->setLongitude(9.99);
        self::assertSame(53.55, $ride->getLatitude());
        self::assertSame(9.99, $ride->getLongitude());

        $ride->setLatitude(null)->setLongitude(null);
        self::assertNull($ride->getLatitude());
        self::assertNull($ride->getLongitude());
    }

    #[Test]
    public function settersAreFluent(): void
    {
        $ride = new Ride();

        self::assertSame($ride, $ride->setTitle('t'));
        self::assertSame($ride, $ride->setCity(new City()));
        self::assertSame($ride, $ride->setDateTime(new \DateTime('2026-06-26')));
        self::assertSame($ride, $ride->setLocation('l'));
        self::assertSame($ride, $ride->setLatitude(1.0));
        self::assertSame($ride, $ride->setLongitude(2.0));
    }

    #[Test]
    public function dateTimeIsStoredByReference(): void
    {
        $dateTime = new \DateTime('2026-06-26 19:00:00');
        $ride = (new Ride())->setDateTime($dateTime);

        $dateTime->modify('+1 day');

        self::assertSame('2026-06-27', $ride->getDateTime()->format('Y-m-d'));
    }

    #[Test]
    public function readingTitleBeforeItIsSetFails(): void
    {
        $this->expectException(\Error::class);
        $this->expectExceptionMessage('must not be accessed before initialization');

        (new Ride())->getTitle();
    }

    #[Test]
    public function readingCityBeforeItIsSetFails(): void
    {
        $this->expectException(\Error::class);

        (new Ride())->getCity();
    }
}
