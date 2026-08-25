<?php declare(strict_types=1);

namespace App\Tests\ForecastRetriever;

use App\Entity\Weather;
use App\ForecastRetriever\WeatherForecastRetriever;
use App\Tests\Fixture\Fixtures;
use Cmfcmf\OpenWeatherMap;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Psr18Client;
use Symfony\Component\HttpClient\Response\MockResponse;

final class WeatherForecastRetrieverTest extends TestCase
{
    /** @var list<string> */
    private array $requestedUrls = [];

    /** @var list<string> */
    private array $alerts = [];

    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->requestedUrls = [];
        $this->alerts = [];

        $this->logger = $this->createStub(LoggerInterface::class);
        $this->logger->method('alert')->willReturnCallback(function (string|\Stringable $message): void {
            $this->alerts[] = (string) $message;
        });
    }

    /**
     * The retriever builds its own Psr18Client in the constructor; swap the OpenWeatherMap
     * instance for one backed by a MockHttpClient so no request ever leaves the process.
     *
     * @param list<MockResponse>|callable $responses
     */
    private function createRetriever(array|callable $responses, string $apiKey = 'test-api-key'): WeatherForecastRetriever
    {
        $retriever = new WeatherForecastRetriever($this->logger, $apiKey);

        $mockHttpClient = new MockHttpClient(function (string $method, string $url, array $options) use ($responses): MockResponse {
            $this->requestedUrls[] = $url;

            if (is_callable($responses)) {
                return $responses($method, $url, $options);
            }

            static $index = 0;

            return $responses[$index++];
        });

        $psr18Client = new Psr18Client($mockHttpClient);
        Fixtures::setPrivateProperty($retriever, 'openWeatherMap', new OpenWeatherMap($apiKey, $psr18Client, $psr18Client));

        return $retriever;
    }

    #[Test]
    public function requestsMetricGermanDailyForecastForRideCoordinates(): void
    {
        $retriever = $this->createRetriever([new MockResponse(Fixtures::forecastXml([['day' => '2026-06-26']]))], 'secret-key');

        $retriever->retrieveWeatherForecastsForRideList([Fixtures::ride(latitude: 53.55, longitude: 9.99)]);

        self::assertCount(1, $this->requestedUrls);
        $url = $this->requestedUrls[0];
        self::assertStringStartsWith('https://api.openweathermap.org/data/2.5/forecast/daily?', $url);
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
        self::assertSame([
            'lat' => '53.55',
            'lon' => '9.99',
            'units' => 'metric',
            'lang' => 'de',
            'mode' => 'xml',
            'APPID' => 'secret-key',
            'cnt' => '5',
        ], $query);
    }

    #[Test]
    public function returnsWeatherForTheForecastDayMatchingTheRideDate(): void
    {
        $xml = Fixtures::forecastXml([
            ['day' => '2026-06-25', 'symbolNumber' => 800],
            ['day' => '2026-06-26', 'symbolNumber' => 500],
            ['day' => '2026-06-27', 'symbolNumber' => 600],
        ]);
        $retriever = $this->createRetriever([new MockResponse($xml)]);
        $ride = Fixtures::ride(dateTime: '2026-06-26 19:00:00');

        $weatherList = $retriever->retrieveWeatherForecastsForRideList([$ride]);

        self::assertCount(1, $weatherList);
        self::assertInstanceOf(Weather::class, $weatherList[0]);
        self::assertSame(500, $weatherList[0]->getWeatherCode());
        self::assertSame('2026-06-26', $weatherList[0]->getWeatherDateTime()->format('Y-m-d'));
        self::assertSame($ride, $weatherList[0]->getRide());
        self::assertSame([], $this->alerts);
    }

    #[Test]
    public function returnsFirstMatchingDayWhenForecastContainsDuplicates(): void
    {
        $xml = Fixtures::forecastXml([
            ['day' => '2026-06-26', 'symbolNumber' => 111],
            ['day' => '2026-06-26', 'symbolNumber' => 222],
        ]);
        $retriever = $this->createRetriever([new MockResponse($xml)]);

        $weatherList = $retriever->retrieveWeatherForecastsForRideList([Fixtures::ride(dateTime: '2026-06-26 19:00:00')]);

        self::assertSame(111, $weatherList[0]->getWeatherCode());
    }

    #[Test]
    public function yieldsNothingWhenNoForecastDayMatchesTheRideDate(): void
    {
        $xml = Fixtures::forecastXml([
            ['day' => '2026-06-01', 'symbolNumber' => 100],
            ['day' => '2026-06-02', 'symbolNumber' => 200],
        ]);
        $retriever = $this->createRetriever([new MockResponse($xml)]);

        $weatherList = $retriever->retrieveWeatherForecastsForRideList([Fixtures::ride(dateTime: '2026-06-30 19:00:00')]);

        self::assertSame([], $weatherList);
        self::assertCount(1, $this->requestedUrls);
        self::assertSame([], $this->alerts);
    }

    #[Test]
    public function emptyForecastYieldsNothingWithoutWarnings(): void
    {
        $retriever = $this->createRetriever([new MockResponse(Fixtures::forecastXml([]))]);

        set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });

        try {
            $weatherList = $retriever->retrieveWeatherForecastsForRideList([Fixtures::ride()]);
        } finally {
            restore_error_handler();
        }

        self::assertSame([], $weatherList);
        self::assertSame([], $this->alerts);
    }

    #[Test]
    public function skipsRidesWithoutCoordinatesWithoutRequesting(): void
    {
        $retriever = $this->createRetriever([]);

        $weatherList = $retriever->retrieveWeatherForecastsForRideList([
            Fixtures::ride(latitude: null, longitude: null),
            Fixtures::ride(latitude: 53.55, longitude: null),
            Fixtures::ride(latitude: null, longitude: 9.99),
        ]);

        self::assertSame([], $weatherList);
        self::assertSame([], $this->requestedUrls);
        self::assertSame([], $this->alerts);
    }

    #[Test]
    public function zeroCoordinatesAreValidCoordinates(): void
    {
        $retriever = $this->createRetriever([new MockResponse(Fixtures::forecastXml([['day' => '2026-06-26', 'symbolNumber' => 800]]))]);

        $weatherList = $retriever->retrieveWeatherForecastsForRideList([Fixtures::ride(dateTime: '2026-06-26 19:00:00', latitude: 0.0, longitude: 0.0)]);

        self::assertCount(1, $this->requestedUrls);
        self::assertStringContainsString('lat=0&lon=0', $this->requestedUrls[0]);
        self::assertCount(1, $weatherList);
        self::assertSame(800, $weatherList[0]->getWeatherCode());
    }

    #[Test]
    public function emptyRideListYieldsEmptyWeatherList(): void
    {
        $retriever = $this->createRetriever([]);

        self::assertSame([], $retriever->retrieveWeatherForecastsForRideList());
        self::assertSame([], $retriever->retrieveWeatherForecastsForRideList([]));
        self::assertSame([], $this->requestedUrls);
    }

    #[Test]
    public function requestsOneForecastPerRideAndKeepsOrder(): void
    {
        $retriever = $this->createRetriever([
            new MockResponse(Fixtures::forecastXml([['day' => '2026-06-26', 'symbolNumber' => 1]])),
            new MockResponse(Fixtures::forecastXml([['day' => '2026-06-27', 'symbolNumber' => 2]])),
        ]);

        $weatherList = $retriever->retrieveWeatherForecastsForRideList([
            Fixtures::ride(citySlug: 'hamburg', dateTime: '2026-06-26 19:00:00', latitude: 53.55, longitude: 9.99),
            Fixtures::ride(citySlug: 'berlin', dateTime: '2026-06-27 19:00:00', latitude: 52.52, longitude: 13.40),
        ]);

        self::assertCount(2, $this->requestedUrls);
        self::assertStringContainsString('lat=53.55&lon=9.99', $this->requestedUrls[0]);
        self::assertStringContainsString('lat=52.52&lon=13.4', $this->requestedUrls[1]);
        self::assertSame([1, 2], array_map(static fn (Weather $weather): int => $weather->getWeatherCode(), $weatherList));
        self::assertSame(['hamburg', 'berlin'], array_map(static fn (Weather $weather): string => $weather->getRide()->getCity()->getMainSlug()->getSlug(), $weatherList));
    }

    #[Test]
    public function apiErrorPayloadIsLoggedAsAlertAndRideIsSkipped(): void
    {
        $retriever = $this->createRetriever([new MockResponse('{"cod":401,"message":"Invalid API key."}')]);

        $weatherList = $retriever->retrieveWeatherForecastsForRideList([Fixtures::ride()]);

        self::assertSame([], $weatherList);
        self::assertSame(['Cannot retrieve weather data: Invalid API key. (Code 401).'], $this->alerts);
    }

    #[Test]
    public function httpErrorStatusIsLoggedAsAlertAndRideIsSkipped(): void
    {
        $retriever = $this->createRetriever([new MockResponse('Service unavailable', ['http_code' => 503])]);

        $weatherList = $retriever->retrieveWeatherForecastsForRideList([Fixtures::ride()]);

        self::assertSame([], $weatherList);
        self::assertCount(1, $this->alerts);
        self::assertStringContainsString('status code 503', $this->alerts[0]);
    }

    #[Test]
    public function notFoundIsLoggedAsAlert(): void
    {
        $retriever = $this->createRetriever([new MockResponse('{"cod":"404","message":"city not found"}', ['http_code' => 404])]);

        $weatherList = $retriever->retrieveWeatherForecastsForRideList([Fixtures::ride()]);

        self::assertSame([], $weatherList);
        self::assertCount(1, $this->alerts);
    }

    #[Test]
    public function oneFailingRideDoesNotPreventOthersFromBeingRetrieved(): void
    {
        $retriever = $this->createRetriever([
            new MockResponse('{"cod":500,"message":"boom"}'),
            new MockResponse(Fixtures::forecastXml([['day' => '2026-06-26', 'symbolNumber' => 7]])),
        ]);

        $weatherList = $retriever->retrieveWeatherForecastsForRideList([
            Fixtures::ride(citySlug: 'first'),
            Fixtures::ride(citySlug: 'second'),
        ]);

        self::assertCount(1, $weatherList);
        self::assertSame('second', $weatherList[0]->getRide()->getCity()->getMainSlug()->getSlug());
        self::assertSame(['Cannot retrieve weather data: boom (Code 500).'], $this->alerts);
    }
}
