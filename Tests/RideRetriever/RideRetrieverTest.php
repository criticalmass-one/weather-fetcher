<?php declare(strict_types=1);

namespace App\Tests\RideRetriever;

use App\Entity\Ride;
use App\RideRetriever\RideRetriever;
use App\Serializer\CriticalSerializer;
use App\Tests\Fixture\Fixtures;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;

final class RideRetrieverTest extends TestCase
{
    private const string HOSTNAME = 'https://criticalmass.test/';

    /** @var list<array{method: string, url: string}> */
    private array $requests = [];

    protected function setUp(): void
    {
        $this->requests = [];
    }

    /**
     * The retriever builds its own HttpClient in the constructor; replace it with a
     * MockHttpClient that records every request.
     *
     * @param list<MockResponse>|callable $responses
     */
    private function createRetriever(array|callable $responses): RideRetriever
    {
        $retriever = new RideRetriever(new CriticalSerializer(), self::HOSTNAME);

        $client = new MockHttpClient(function (string $method, string $url, array $options) use ($responses): MockResponse {
            $this->requests[] = ['method' => $method, 'url' => $url];

            if (is_callable($responses)) {
                return $responses($method, $url, $options);
            }

            static $index = 0;

            return $responses[$index++];
        }, self::HOSTNAME);

        Fixtures::setPrivateProperty($retriever, 'client', $client);

        return $retriever;
    }

    private function date(string $date): \DateTimeImmutable
    {
        return new \DateTimeImmutable($date, new \DateTimeZone('Europe/Berlin'));
    }

    #[Test]
    public function requestsExtendedRideListForTheStartDay(): void
    {
        $retriever = $this->createRetriever([new MockResponse('[]')]);

        $retriever->retrieveRides($this->date('2026-06-05 10:00:00'), $this->date('2026-06-06 10:00:00'));

        self::assertCount(1, $this->requests);
        self::assertSame('GET', $this->requests[0]['method']);
        self::assertSame(
            'https://criticalmass.test/api/ride?year=2026&month=06&day=05&size=250&extended=1',
            $this->requests[0]['url'],
        );
    }

    #[Test]
    public function alwaysRequestsAtLeastTheStartDayEvenWhenRangeIsEmpty(): void
    {
        $retriever = $this->createRetriever([new MockResponse('[]')]);

        $from = $this->date('2026-06-05');
        $retriever->retrieveRides($from, $from);

        self::assertCount(1, $this->requests);
    }

    #[Test]
    public function requestsStartDayEvenWhenUntilLiesBeforeFrom(): void
    {
        $retriever = $this->createRetriever([new MockResponse('[]')]);

        $retriever->retrieveRides($this->date('2026-06-05'), $this->date('2026-06-01'));

        self::assertCount(1, $this->requests);
        self::assertStringContainsString('year=2026&month=06&day=05', $this->requests[0]['url']);
    }

    #[Test]
    public function requestsOneDayPerCalendarDayExcludingTheEndDate(): void
    {
        $retriever = $this->createRetriever(static fn (): MockResponse => new MockResponse('[]'));

        $retriever->retrieveRides($this->date('2026-06-29'), $this->date('2026-07-02'));

        self::assertSame([
            'https://criticalmass.test/api/ride?year=2026&month=06&day=29&size=250&extended=1',
            'https://criticalmass.test/api/ride?year=2026&month=06&day=30&size=250&extended=1',
            'https://criticalmass.test/api/ride?year=2026&month=07&day=01&size=250&extended=1',
        ], array_column($this->requests, 'url'));
    }

    #[Test]
    public function partialLastDayIsStillRequested(): void
    {
        $retriever = $this->createRetriever(static fn (): MockResponse => new MockResponse('[]'));

        // 2026-06-05 10:00 -> +1 day = 06-06 10:00 which is < 06-06 12:00, so 06-06 gets fetched too
        $retriever->retrieveRides($this->date('2026-06-05 10:00:00'), $this->date('2026-06-06 12:00:00'));

        self::assertCount(2, $this->requests);
        self::assertStringContainsString('day=06', $this->requests[1]['url']);
    }

    #[Test]
    public function deserializesRidesFromResponse(): void
    {
        $json = Fixtures::rideListJson([
            ['title' => 'CM Hamburg', 'city' => ['name' => 'Hamburg', 'main_slug' => ['slug' => 'hamburg']]],
            ['title' => 'CM Berlin', 'city' => ['name' => 'Berlin', 'main_slug' => ['slug' => 'berlin']]],
        ]);
        $retriever = $this->createRetriever([new MockResponse($json)]);

        $rides = $retriever->retrieveRides($this->date('2026-06-26'), $this->date('2026-06-27'));

        self::assertCount(2, $rides);
        self::assertContainsOnlyInstancesOf(Ride::class, $rides);
        self::assertSame('CM Hamburg', $rides[0]->getTitle());
        self::assertSame('berlin', $rides[1]->getCity()->getMainSlug()->getSlug());
        self::assertSame(1782493200, $rides[0]->getDateTime()->getTimestamp());
    }

    #[Test]
    public function emptyDaysProduceEmptyResult(): void
    {
        $retriever = $this->createRetriever(static fn (): MockResponse => new MockResponse('[]'));

        self::assertSame([], $retriever->retrieveRides($this->date('2026-06-01'), $this->date('2026-06-04')));
    }

    /**
     * Documents current behaviour: the per-day lists are merged with the array union
     * operator (+=), which keeps the FIRST value for every numeric key. Rides from later
     * days therefore silently overwrite nothing and are dropped whenever an earlier day
     * already produced a ride with the same index.
     */
    #[Test]
    public function ridesOfLaterDaysAreLostWhenIndexesCollide(): void
    {
        $retriever = $this->createRetriever([
            new MockResponse(Fixtures::rideListJson([['title' => 'day1-a'], ['title' => 'day1-b']])),
            new MockResponse(Fixtures::rideListJson([['title' => 'day2-a']])),
            new MockResponse(Fixtures::rideListJson([['title' => 'day3-a'], ['title' => 'day3-b'], ['title' => 'day3-c']])),
        ]);

        $rides = $retriever->retrieveRides($this->date('2026-06-01'), $this->date('2026-06-04'));

        self::assertSame(
            ['day1-a', 'day1-b', 'day3-c'],
            array_map(static fn (Ride $ride): string => $ride->getTitle(), $rides),
        );
    }

    /**
     * Documents current behaviour: DateTimeInterface::add() is called on the caller's
     * object, so a mutable DateTime passed as "from" is advanced to the end of the range.
     */
    #[Test]
    public function mutableFromDateIsAdvancedAsSideEffect(): void
    {
        $retriever = $this->createRetriever(static fn (): MockResponse => new MockResponse('[]'));
        $from = new \DateTime('2026-06-01 00:00:00');
        $until = new \DateTime('2026-06-03 00:00:00');

        $retriever->retrieveRides($from, $until);

        self::assertSame('2026-06-03', $from->format('Y-m-d'));
        self::assertCount(2, $this->requests);
    }

    #[Test]
    public function immutableFromDateIsLeftUntouched(): void
    {
        $retriever = $this->createRetriever(static fn (): MockResponse => new MockResponse('[]'));
        $from = $this->date('2026-06-01');

        $retriever->retrieveRides($from, $this->date('2026-06-03'));

        self::assertSame('2026-06-01', $from->format('Y-m-d'));
    }

    #[Test]
    public function clientErrorResponseThrows(): void
    {
        $retriever = $this->createRetriever([new MockResponse('{"error":"nope"}', ['http_code' => 404])]);

        $this->expectException(ClientExceptionInterface::class);

        $retriever->retrieveRides($this->date('2026-06-01'), $this->date('2026-06-02'));
    }

    #[Test]
    public function serverErrorResponseThrows(): void
    {
        $retriever = $this->createRetriever([new MockResponse('', ['http_code' => 500])]);

        $this->expectException(ServerExceptionInterface::class);

        $retriever->retrieveRides($this->date('2026-06-01'), $this->date('2026-06-02'));
    }

    #[Test]
    public function serverErrorOnSecondDayAbortsTheWholeRange(): void
    {
        $retriever = $this->createRetriever([
            new MockResponse(Fixtures::rideListJson([['title' => 'ok']])),
            new MockResponse('', ['http_code' => 500]),
        ]);

        try {
            $retriever->retrieveRides($this->date('2026-06-01'), $this->date('2026-06-03'));
            self::fail('Expected a server exception');
        } catch (ServerExceptionInterface) {
            self::assertCount(2, $this->requests);
        }
    }
}
