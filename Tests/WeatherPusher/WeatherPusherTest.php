<?php declare(strict_types=1);

namespace App\Tests\WeatherPusher;

use App\Entity\Weather;
use App\Serializer\CriticalSerializer;
use App\Tests\Fixture\Fixtures;
use App\WeatherPusher\WeatherPusher;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class WeatherPusherTest extends TestCase
{
    private const string HOSTNAME = 'https://criticalmass.test/';

    /** @var list<array{method: string, url: string, body: string}> */
    private array $requests = [];

    protected function setUp(): void
    {
        $this->requests = [];
    }

    private function createPusher(MockResponse $response): WeatherPusher
    {
        $pusher = new WeatherPusher(new CriticalSerializer(), self::HOSTNAME);

        $client = new MockHttpClient(function (string $method, string $url, array $options) use ($response): MockResponse {
            $this->requests[] = ['method' => $method, 'url' => $url, 'body' => (string) $options['body']];

            return $response;
        }, self::HOSTNAME);

        Fixtures::setPrivateProperty($pusher, 'client', $client);

        return $pusher;
    }

    #[Test]
    public function putsWeatherToTheCityAndDateSpecificEndpoint(): void
    {
        $pusher = $this->createPusher(new MockResponse('', ['http_code' => 201]));
        $weather = Fixtures::weather(Fixtures::ride(citySlug: 'hamburg', dateTime: '2026-06-26 19:00:00'));

        $pusher->pushWeather($weather);

        self::assertCount(1, $this->requests);
        self::assertSame('PUT', $this->requests[0]['method']);
        self::assertSame('https://criticalmass.test/api/hamburg/2026-06-26/weather', $this->requests[0]['url']);
    }

    #[Test]
    public function urlUsesTheRideDateNotTheWeatherDate(): void
    {
        $pusher = $this->createPusher(new MockResponse('', ['http_code' => 201]));
        $weather = Fixtures::weather(
            Fixtures::ride(citySlug: 'berlin', dateTime: '2026-07-31 19:00:00'),
            '2026-07-30 00:00:00',
        );

        $pusher->pushWeather($weather);

        self::assertSame('https://criticalmass.test/api/berlin/2026-07-31/weather', $this->requests[0]['url']);
    }

    #[Test]
    public function bodyIsTheSerializedWeatherWithoutNulls(): void
    {
        $pusher = $this->createPusher(new MockResponse('', ['http_code' => 201]));
        $weather = Fixtures::weather()->setJson('{"raw":1}')->setWindSpeed(null);

        $pusher->pushWeather($weather);

        $body = json_decode($this->requests[0]['body'], true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('{"raw":1}', $body['json']);
        self::assertSame(12.5, $body['temperature_min']);
        self::assertSame(500, $body['weather_code']);
        self::assertSame('10d', $body['weather_icon']);
        self::assertSame(1782432000, $body['weather_date_time']);
        self::assertSame('hamburg', $body['ride']['city']['main_slug']['slug']);
        self::assertArrayNotHasKey('wind_speed', $body);
        self::assertArrayNotHasKey('pressure', $body);
    }

    #[Test]
    public function returnsTrueOnlyForHttpCreated(): void
    {
        $pusher = $this->createPusher(new MockResponse('', ['http_code' => 201]));

        self::assertTrue($pusher->pushWeather(Fixtures::weather()));
    }

    /**
     * @return iterable<string, array{int}>
     */
    public static function nonCreatedStatusCodes(): iterable
    {
        yield 'ok' => [200];
        yield 'no content' => [204];
        yield 'bad request' => [400];
        yield 'unauthorized' => [401];
        yield 'not found' => [404];
        yield 'server error' => [500];
    }

    /**
     * Only the status code is inspected, so 4xx/5xx do not raise the HttpClient exceptions
     * that UpdateWeatherCommand is prepared to catch; the push simply reports false.
     */
    #[Test]
    #[DataProvider('nonCreatedStatusCodes')]
    public function returnsFalseWithoutThrowingForOtherStatusCodes(int $statusCode): void
    {
        $pusher = $this->createPusher(new MockResponse('{"error":"x"}', ['http_code' => $statusCode]));

        self::assertFalse($pusher->pushWeather(Fixtures::weather()));
        self::assertCount(1, $this->requests);
    }

    #[Test]
    public function eachPushIssuesItsOwnRequest(): void
    {
        $pusher = $this->createPusher(new MockResponse('', ['http_code' => 201]));

        $pusher->pushWeather(Fixtures::weather(Fixtures::ride(citySlug: 'a')));
        $pusher->pushWeather(Fixtures::weather(Fixtures::ride(citySlug: 'b')));

        self::assertSame([
            'https://criticalmass.test/api/a/2026-06-26/weather',
            'https://criticalmass.test/api/b/2026-06-26/weather',
        ], array_column($this->requests, 'url'));
    }

    #[Test]
    public function weatherWithoutRideCannotBePushed(): void
    {
        $pusher = $this->createPusher(new MockResponse('', ['http_code' => 201]));

        $this->expectException(\Error::class);

        $pusher->pushWeather(new Weather());
    }
}
