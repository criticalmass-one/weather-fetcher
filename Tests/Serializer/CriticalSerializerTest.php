<?php declare(strict_types=1);

namespace App\Tests\Serializer;

use App\Entity\Ride;
use App\Entity\Weather;
use App\Serializer\CriticalSerializer;
use App\Serializer\CriticalSerializerInterface;
use App\Tests\Fixture\Fixtures;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;

final class CriticalSerializerTest extends TestCase
{
    private CriticalSerializer $serializer;

    protected function setUp(): void
    {
        $this->serializer = new CriticalSerializer();
    }

    #[Test]
    public function defaultFormatIsJson(): void
    {
        self::assertSame('json', CriticalSerializerInterface::FORMAT);
        self::assertSame('{"foo":"bar"}', $this->serializer->serialize(['foo' => 'bar']));
    }

    #[Test]
    public function deserializesRideListFromApiPayload(): void
    {
        $json = Fixtures::rideListJson([[
            'id' => 42,
            'title' => 'Critical Mass Hamburg',
            'date_time' => 1782493200,
            'location' => 'Rathausmarkt',
            'latitude' => 53.55,
            'longitude' => 9.99,
            'city' => [
                'id' => 1,
                'name' => 'Hamburg',
                'main_slug' => ['id' => 7, 'slug' => 'hamburg'],
            ],
        ]]);

        /** @var Ride[] $rides */
        $rides = $this->serializer->deserialize($json, Ride::class.'[]', 'json');

        self::assertCount(1, $rides);
        $ride = $rides[0];
        self::assertInstanceOf(Ride::class, $ride);
        self::assertSame('Critical Mass Hamburg', $ride->getTitle());
        self::assertSame(1782493200, $ride->getDateTime()->getTimestamp());
        self::assertSame('Rathausmarkt', $ride->getLocation());
        self::assertTrue($ride->hasLocation());
        self::assertSame(53.55, $ride->getLatitude());
        self::assertSame(9.99, $ride->getLongitude());
        self::assertSame('Hamburg', $ride->getCity()->getName());
        self::assertSame('hamburg', $ride->getCity()->getMainSlug()->getSlug());
    }

    #[Test]
    public function idsHaveNoSetterAndAreSilentlyDropped(): void
    {
        $json = Fixtures::rideListJson([['id' => 42, 'city' => ['id' => 99, 'name' => 'X', 'main_slug' => ['id' => 5, 'slug' => 'x']]]]);

        /** @var Ride[] $rides */
        $rides = $this->serializer->deserialize($json, Ride::class.'[]', 'json');

        self::assertNull($rides[0]->getCity()->getId());
    }

    #[Test]
    public function unknownAttributesAreIgnored(): void
    {
        $json = Fixtures::rideListJson([['some_unknown_field' => 'x', 'city' => ['name' => 'X', 'main_slug' => ['slug' => 'x'], 'timezone' => 'Europe/Berlin']]]);

        /** @var Ride[] $rides */
        $rides = $this->serializer->deserialize($json, Ride::class.'[]', 'json');

        self::assertCount(1, $rides);
        self::assertSame('X', $rides[0]->getCity()->getName());
    }

    #[Test]
    public function deserializesMultipleRidesPreservingOrder(): void
    {
        $json = Fixtures::rideListJson([['title' => 'first'], ['title' => 'second'], ['title' => 'third']]);

        /** @var Ride[] $rides */
        $rides = $this->serializer->deserialize($json, Ride::class.'[]', 'json');

        self::assertSame(['first', 'second', 'third'], array_map(static fn (Ride $ride): string => $ride->getTitle(), $rides));
        self::assertSame([0, 1, 2], array_keys($rides));
    }

    #[Test]
    public function nullCoordinatesAndMissingLocationStayNull(): void
    {
        $json = json_encode([[
            'title' => 'T',
            'date_time' => 1782493200,
            'latitude' => null,
            'longitude' => null,
            'city' => ['name' => 'X', 'main_slug' => ['slug' => 'x']],
        ]]);

        /** @var Ride[] $rides */
        $rides = $this->serializer->deserialize($json, Ride::class.'[]', 'json');

        self::assertNull($rides[0]->getLatitude());
        self::assertNull($rides[0]->getLongitude());
        self::assertNull($rides[0]->getLocation());
        self::assertFalse($rides[0]->hasLocation());
    }

    #[Test]
    public function integerCoordinatesAreCoercedToFloat(): void
    {
        $json = Fixtures::rideListJson([['latitude' => 53, 'longitude' => 10]]);

        /** @var Ride[] $rides */
        $rides = $this->serializer->deserialize($json, Ride::class.'[]', 'json');

        self::assertSame(53.0, $rides[0]->getLatitude());
        self::assertSame(10.0, $rides[0]->getLongitude());
    }

    #[Test]
    public function numericStringTimestampIsAcceptedViaDateTimeNormalizer(): void
    {
        $json = Fixtures::rideListJson([['date_time' => '1782493200']]);

        /** @var Ride[] $rides */
        $rides = $this->serializer->deserialize($json, Ride::class.'[]', 'json');

        self::assertSame(1782493200, $rides[0]->getDateTime()->getTimestamp());
    }

    #[Test]
    public function isoDateStringIsAcceptedViaDateTimeNormalizer(): void
    {
        $json = Fixtures::rideListJson([['date_time' => '2026-06-26T19:00:00+02:00']]);

        /** @var Ride[] $rides */
        $rides = $this->serializer->deserialize($json, Ride::class.'[]', 'json');

        self::assertSame('2026-06-26T19:00:00+02:00', $rides[0]->getDateTime()->format(\DateTimeInterface::ATOM));
    }

    #[Test]
    public function emptyListDeserializesToEmptyArray(): void
    {
        self::assertSame([], $this->serializer->deserialize('[]', Ride::class.'[]', 'json'));
    }

    #[Test]
    public function invalidJsonThrows(): void
    {
        $this->expectException(NotEncodableValueException::class);

        $this->serializer->deserialize('not json', Ride::class.'[]', 'json');
    }

    #[Test]
    public function serializesWeatherWithSnakeCaseKeysTimestampsAndWithoutNulls(): void
    {
        $weather = Fixtures::weather()->setJson('{"raw":true}');

        $json = $this->serializer->serialize($weather, 'json');
        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        self::assertSame([
            'json' => '{"raw":true}',
            'ride' => [
                'title' => 'Critical Mass Hamburg',
                'city' => [
                    'main_slug' => ['slug' => 'hamburg'],
                    'name' => 'Hamburg',
                ],
                'date_time' => 1782493200,
                'location' => 'Rathausmarkt',
                'latitude' => 53.55,
                'longitude' => 9.99,
            ],
            'weather_date_time' => 1782432000,
            'creation_date_time' => 1781956800,
            'temperature_min' => 12.5,
            'temperature_max' => 21.0,
            'weather_code' => 500,
            'weather' => 'light rain',
            'weather_description' => 'light rain',
            'weather_icon' => '10d',
        ], $decoded);
    }

    #[Test]
    public function nullValuesAreSkippedEvenWhenCallerPassesContext(): void
    {
        $weather = (new Weather())
            ->setRide(Fixtures::ride())
            ->setCreationDateTime(null)
            ->setTemperatureMin(null);

        $decoded = json_decode($this->serializer->serialize($weather, 'json', ['some' => 'context']), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(['ride'], array_keys($decoded));
    }

    #[Test]
    public function dateTimesAreSerializedAsIntegerTimestamps(): void
    {
        $weather = (new Weather())
            ->setRide(Fixtures::ride())
            ->setCreationDateTime(null)
            ->setWeatherDateTime(new \DateTime('2026-06-26 00:00:00', new \DateTimeZone('UTC')));

        $decoded = json_decode($this->serializer->serialize($weather), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(1782432000, $decoded['weather_date_time']);
        self::assertIsInt($decoded['ride']['date_time']);
    }

    #[Test]
    public function rideWithoutLocationIsSerializedWithoutLocationKey(): void
    {
        $weather = (new Weather())
            ->setRide(Fixtures::ride(location: null, latitude: null, longitude: null))
            ->setCreationDateTime(null);

        $decoded = json_decode($this->serializer->serialize($weather), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(['title', 'city', 'date_time'], array_keys($decoded['ride']));
    }

    /**
     * Uninitialised typed properties on a bare Ride are swallowed by the normalizer:
     * the ride becomes an empty JSON array instead of an error.
     */
    #[Test]
    public function uninitialisedRideSerializesToEmptyArray(): void
    {
        $weather = (new Weather())->setRide(new Ride())->setCreationDateTime(null);

        self::assertSame('{"ride":[]}', $this->serializer->serialize($weather));
    }

    #[Test]
    public function rideSurvivesSerializeDeserializeRoundTrip(): void
    {
        $original = Fixtures::ride();

        $json = $this->serializer->serialize($original);
        /** @var Ride $restored */
        $restored = $this->serializer->deserialize($json, Ride::class);

        self::assertSame($original->getTitle(), $restored->getTitle());
        self::assertSame($original->getDateTime()->getTimestamp(), $restored->getDateTime()->getTimestamp());
        self::assertSame($original->getLocation(), $restored->getLocation());
        self::assertSame($original->getLatitude(), $restored->getLatitude());
        self::assertSame($original->getLongitude(), $restored->getLongitude());
        self::assertSame($original->getCity()->getName(), $restored->getCity()->getName());
        self::assertSame($original->getCity()->getMainSlug()->getSlug(), $restored->getCity()->getMainSlug()->getSlug());
    }
}
