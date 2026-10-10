<?php declare(strict_types=1);

namespace App\Tests\RideRetriever;

use App\Entity\Ride;
use App\RideRetriever\RideRetriever;
use App\Serializer\CriticalSerializerInterface;
use PHPUnit\Framework\TestCase;

class RideRetrieverTest extends TestCase
{
    public function testKeepsTheRidesOfEveryDay(): void
    {
        $retriever = new class($this->createStub(CriticalSerializerInterface::class), 'https://example.org') extends RideRetriever {
            protected function retrieveRidesForDate(\DateTimeInterface $dateTime): array
            {
                return [new Ride(), new Ride()];
            }
        };

        $rides = $retriever->retrieveRides(new \DateTimeImmutable('2026-10-01'), new \DateTimeImmutable('2026-10-04'));

        $this->assertCount(6, $rides);
    }
}
