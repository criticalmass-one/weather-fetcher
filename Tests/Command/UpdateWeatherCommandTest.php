<?php declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\UpdateWeatherCommand;
use App\Entity\Weather;
use App\ForecastRetriever\WeatherForecastRetrieverInterface;
use App\RideRetriever\RideRetrieverInterface;
use App\Tests\Fixture\Fixtures;
use App\WeatherPusher\WeatherPusherInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class UpdateWeatherCommandTest extends TestCase
{
    private RideRetrieverInterface&Stub $rideRetriever;
    private WeatherForecastRetrieverInterface&Stub $forecastRetriever;
    private WeatherPusherInterface&Stub $weatherPusher;
    private CommandTester $tester;

    protected function setUp(): void
    {
        $this->rideRetriever = $this->createStub(RideRetrieverInterface::class);
        $this->forecastRetriever = $this->createStub(WeatherForecastRetrieverInterface::class);
        $this->weatherPusher = $this->createStub(WeatherPusherInterface::class);

        $this->rideRetriever->method('retrieveRides')->willReturn([]);
        $this->forecastRetriever->method('retrieveWeatherForecastsForRideList')->willReturn([]);
        $this->weatherPusher->method('pushWeather')->willReturn(true);
    }

    /**
     * @param array<string, string> $input
     */
    private function execute(array $input): int
    {
        $this->tester = new CommandTester(new UpdateWeatherCommand($this->rideRetriever, $this->forecastRetriever, $this->weatherPusher));

        return $this->tester->execute($input);
    }

    private function display(): string
    {
        return (string) preg_replace('/\s+/', ' ', $this->tester->getDisplay(true));
    }

    #[Test]
    public function isNamedAndDescribed(): void
    {
        $command = new UpdateWeatherCommand($this->rideRetriever, $this->forecastRetriever, $this->weatherPusher);

        self::assertSame('criticalmass:weather:update', $command->getName());
        self::assertSame('Retrieve weather forecasts for parameterized range', $command->getDescription());
        self::assertTrue($command->getDefinition()->hasArgument('from'));
        self::assertTrue($command->getDefinition()->hasArgument('until'));
        self::assertFalse($command->getDefinition()->getArgument('from')->isRequired());
        self::assertFalse($command->getDefinition()->getArgument('until')->isRequired());
    }

    #[Test]
    public function passesExplicitRangeToRideRetriever(): void
    {
        $rideRetriever = $this->createMock(RideRetrieverInterface::class);
        $rideRetriever->expects(self::once())
            ->method('retrieveRides')
            ->with(
                self::callback(static fn (\DateTimeInterface $from): bool => $from instanceof \DateTimeImmutable && '2026-06-01 00:00:00' === $from->format('Y-m-d H:i:s')),
                self::callback(static fn (\DateTimeInterface $until): bool => $until instanceof \DateTimeImmutable && '2026-06-10 12:30:00' === $until->format('Y-m-d H:i:s')),
            )
            ->willReturn([]);
        $this->rideRetriever = $rideRetriever;

        $exitCode = $this->execute(['from' => '2026-06-01', 'until' => '2026-06-10 12:30']);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('Retrieved 0 rides from 2026-06-01 until 2026-06-10', $this->display());
    }

    #[Test]
    public function untilDefaultsToOneWeekAfterFrom(): void
    {
        $rideRetriever = $this->createMock(RideRetrieverInterface::class);
        $rideRetriever->expects(self::once())
            ->method('retrieveRides')
            ->with(
                self::callback(static fn (\DateTimeInterface $from): bool => '2026-06-01' === $from->format('Y-m-d')),
                self::callback(static fn (\DateTimeInterface $until): bool => '2026-06-08 00:00:00' === $until->format('Y-m-d H:i:s')),
            )
            ->willReturn([]);
        $this->rideRetriever = $rideRetriever;

        $this->execute(['from' => '2026-06-01']);

        self::assertStringContainsString('from 2026-06-01 until 2026-06-08', $this->display());
    }

    #[Test]
    public function fromDefaultsToNowAndUntilToOneWeekLater(): void
    {
        $captured = [];
        $this->rideRetriever = $this->createStub(RideRetrieverInterface::class);
        $this->rideRetriever->method('retrieveRides')
            ->willReturnCallback(static function (\DateTimeInterface $from, \DateTimeInterface $until) use (&$captured): array {
                $captured = [$from, $until];

                return [];
            });

        $before = new \DateTimeImmutable();
        $this->execute([]);
        $after = new \DateTimeImmutable();

        self::assertCount(2, $captured);
        [$from, $until] = $captured;
        self::assertGreaterThanOrEqual($before, $from);
        self::assertLessThanOrEqual($after, $from);
        self::assertSame('7 0 0 0', $from->diff($until)->format('%a %h %i %s'));
    }

    #[Test]
    public function relativeDateExpressionsAreAccepted(): void
    {
        $rideRetriever = $this->createMock(RideRetrieverInterface::class);
        $rideRetriever->expects(self::once())
            ->method('retrieveRides')
            ->with(
                self::callback(static fn (\DateTimeInterface $from): bool => '2026-06-01' === $from->format('Y-m-d')),
                self::callback(static fn (\DateTimeInterface $until): bool => '2026-06-04' === $until->format('Y-m-d')),
            )
            ->willReturn([]);
        $this->rideRetriever = $rideRetriever;

        $this->execute(['from' => '2026-06-01', 'until' => '2026-06-01 +3 days']);
    }

    #[Test]
    public function invalidDateArgumentThrowsBeforeAnythingIsRetrieved(): void
    {
        $rideRetriever = $this->createMock(RideRetrieverInterface::class);
        $rideRetriever->expects(self::never())->method('retrieveRides');
        $this->rideRetriever = $rideRetriever;

        $this->expectException(\DateMalformedStringException::class);

        $this->execute(['from' => 'not a date']);
    }

    #[Test]
    public function listsRetrievedRidesInATable(): void
    {
        $rides = [
            Fixtures::ride(cityName: 'Hamburg', title: 'CM Hamburg', dateTime: '2026-06-26 19:00:00', location: 'Rathausmarkt', latitude: 53.55, longitude: 9.99),
            Fixtures::ride(cityName: 'Berlin', title: 'CM Berlin', dateTime: '2026-06-26 20:00:00', location: null, latitude: null, longitude: null),
        ];
        $this->rideRetriever = $this->createStub(RideRetrieverInterface::class);
        $this->rideRetriever->method('retrieveRides')->willReturn($rides);

        $forecastRetriever = $this->createMock(WeatherForecastRetrieverInterface::class);
        $forecastRetriever->expects(self::once())->method('retrieveWeatherForecastsForRideList')->with($rides)->willReturn([]);
        $this->forecastRetriever = $forecastRetriever;

        $this->execute(['from' => '2026-06-26']);

        $output = $this->display();
        self::assertStringContainsString('Retrieved 2 rides from 2026-06-26 until 2026-07-03', $output);
        self::assertMatchesRegularExpression('/City\s+DateTime\s+Title\s+Location\s+Latitude\s+Longitude/', $output);
        self::assertMatchesRegularExpression('/Hamburg\s+2026-06-26 19-00-00\s+CM Hamburg\s+Rathausmarkt\s+53\.55\s+9\.99/', $output);
        self::assertMatchesRegularExpression('/Berlin\s+2026-06-26 20-00-00\s+CM Berlin\s+-{5}/', $output);
        self::assertStringContainsString('Retrieved 0 weather data items for 2 rides', $output);
    }

    #[Test]
    public function pushesEveryWeatherItemAndReportsSuccessCount(): void
    {
        $rides = [Fixtures::ride(cityName: 'Hamburg'), Fixtures::ride(cityName: 'Berlin', citySlug: 'berlin')];
        $weatherList = [
            Fixtures::weather($rides[0])->setWeatherDescription('light rain'),
            Fixtures::weather($rides[1])->setWeatherDescription('clear sky'),
        ];
        $this->rideRetriever = $this->createStub(RideRetrieverInterface::class);
        $this->rideRetriever->method('retrieveRides')->willReturn($rides);
        $this->forecastRetriever = $this->createStub(WeatherForecastRetrieverInterface::class);
        $this->forecastRetriever->method('retrieveWeatherForecastsForRideList')->willReturn($weatherList);

        $pushed = [];
        $weatherPusher = $this->createMock(WeatherPusherInterface::class);
        $weatherPusher->expects(self::exactly(2))
            ->method('pushWeather')
            ->willReturnCallback(static function (Weather $weather) use (&$pushed): bool {
                $pushed[] = $weather;

                return true;
            });
        $this->weatherPusher = $weatherPusher;

        $exitCode = $this->execute(['from' => '2026-06-26']);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertSame($weatherList, $pushed);
        $output = $this->display();
        self::assertStringContainsString('Retrieved 2 weather data items for 2 rides', $output);
        self::assertMatchesRegularExpression('/City\s+Weather DateTime\s+Weather Description/', $output);
        self::assertMatchesRegularExpression('/Hamburg\s+2026-06-26 00-00-00\s+light rain/', $output);
        self::assertMatchesRegularExpression('/Berlin\s+2026-06-26 00-00-00\s+clear sky/', $output);
        self::assertStringContainsString('Pushed 2 weather data items to api', $output);
        self::assertStringNotContainsString('Could not push', $output);
    }

    #[Test]
    public function reportsItemsThePusherRejected(): void
    {
        $rides = [Fixtures::ride(), Fixtures::ride(), Fixtures::ride()];
        $weatherList = [Fixtures::weather($rides[0]), Fixtures::weather($rides[1]), Fixtures::weather($rides[2])];
        $this->rideRetriever = $this->createStub(RideRetrieverInterface::class);
        $this->rideRetriever->method('retrieveRides')->willReturn($rides);
        $this->forecastRetriever = $this->createStub(WeatherForecastRetrieverInterface::class);
        $this->forecastRetriever->method('retrieveWeatherForecastsForRideList')->willReturn($weatherList);
        $this->weatherPusher = $this->createStub(WeatherPusherInterface::class);
        $this->weatherPusher->method('pushWeather')->willReturn(true, false, true);

        $exitCode = $this->execute(['from' => '2026-06-26']);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('Pushed 2 weather data items to api', $this->display());
        self::assertStringContainsString('Could not push 1 weather data items to api', $this->display());
    }

    #[Test]
    public function stillSucceedsWhenNothingCouldBePushed(): void
    {
        $ride = Fixtures::ride();
        $this->rideRetriever = $this->createStub(RideRetrieverInterface::class);
        $this->rideRetriever->method('retrieveRides')->willReturn([$ride]);
        $this->forecastRetriever = $this->createStub(WeatherForecastRetrieverInterface::class);
        $this->forecastRetriever->method('retrieveWeatherForecastsForRideList')->willReturn([Fixtures::weather($ride)]);
        $this->weatherPusher = $this->createStub(WeatherPusherInterface::class);
        $this->weatherPusher->method('pushWeather')->willReturn(false);

        $exitCode = $this->execute(['from' => '2026-06-26']);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringNotContainsString('Pushed', $this->display());
        self::assertStringContainsString('Could not push 1 weather data items to api', $this->display());
    }

    #[Test]
    public function doesNotPushOrReportWhenThereIsNoWeather(): void
    {
        $forecastRetriever = $this->createMock(WeatherForecastRetrieverInterface::class);
        $forecastRetriever->expects(self::once())->method('retrieveWeatherForecastsForRideList')->with([])->willReturn([]);
        $this->forecastRetriever = $forecastRetriever;

        $weatherPusher = $this->createMock(WeatherPusherInterface::class);
        $weatherPusher->expects(self::never())->method('pushWeather');
        $this->weatherPusher = $weatherPusher;

        $exitCode = $this->execute(['from' => '2026-06-26']);

        self::assertSame(Command::SUCCESS, $exitCode);
        $output = $this->display();
        self::assertStringContainsString('Retrieved 0 rides', $output);
        self::assertStringContainsString('Retrieved 0 weather data items for 0 rides', $output);
        self::assertStringNotContainsString('Pushed', $output);
        self::assertStringNotContainsString('Could not push', $output);
    }

    #[Test]
    public function serverErrorWhilePushingIsReportedAsWarningAndOthersContinue(): void
    {
        $rides = [Fixtures::ride(cityName: 'Hamburg'), Fixtures::ride(cityName: 'Berlin')];
        $weatherList = [Fixtures::weather($rides[0]), Fixtures::weather($rides[1])];
        $this->rideRetriever = $this->createStub(RideRetrieverInterface::class);
        $this->rideRetriever->method('retrieveRides')->willReturn($rides);
        $this->forecastRetriever = $this->createStub(WeatherForecastRetrieverInterface::class);
        $this->forecastRetriever->method('retrieveWeatherForecastsForRideList')->willReturn($weatherList);

        $weatherPusher = $this->createMock(WeatherPusherInterface::class);
        $weatherPusher->expects(self::exactly(2))
            ->method('pushWeather')
            ->willReturnCallback(function (Weather $weather): bool {
                if ('Hamburg' === $weather->getRide()->getCity()->getName()) {
                    throw $this->httpException(ServerExceptionInterface::class, 'HTTP 503 returned');
                }

                return true;
            });
        $this->weatherPusher = $weatherPusher;

        $exitCode = $this->execute(['from' => '2026-06-26']);

        self::assertSame(Command::SUCCESS, $exitCode);
        $output = $this->display();
        self::assertStringContainsString('Server error pushing weather for Hamburg: HTTP 503 returned', $output);
        self::assertStringContainsString('Pushed 1 weather data items to api', $output);
        self::assertStringContainsString('Could not push 1 weather data items to api', $output);
    }

    #[Test]
    public function clientErrorWhilePushingIsReportedAsWarning(): void
    {
        $ride = Fixtures::ride(cityName: 'Hamburg');
        $this->rideRetriever = $this->createStub(RideRetrieverInterface::class);
        $this->rideRetriever->method('retrieveRides')->willReturn([$ride]);
        $this->forecastRetriever = $this->createStub(WeatherForecastRetrieverInterface::class);
        $this->forecastRetriever->method('retrieveWeatherForecastsForRideList')->willReturn([Fixtures::weather($ride)]);
        $this->weatherPusher = $this->createStub(WeatherPusherInterface::class);
        $this->weatherPusher->method('pushWeather')
            ->willThrowException($this->httpException(ClientExceptionInterface::class, 'HTTP 404 returned'));

        $exitCode = $this->execute(['from' => '2026-06-26']);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('Client error pushing weather for Hamburg: HTTP 404 returned', $this->display());
        self::assertStringContainsString('Could not push 1 weather data items to api', $this->display());
    }

    #[Test]
    public function unexpectedExceptionsWhilePushingBubbleUp(): void
    {
        $ride = Fixtures::ride();
        $this->rideRetriever = $this->createStub(RideRetrieverInterface::class);
        $this->rideRetriever->method('retrieveRides')->willReturn([$ride]);
        $this->forecastRetriever = $this->createStub(WeatherForecastRetrieverInterface::class);
        $this->forecastRetriever->method('retrieveWeatherForecastsForRideList')->willReturn([Fixtures::weather($ride)]);
        $this->weatherPusher = $this->createStub(WeatherPusherInterface::class);
        $this->weatherPusher->method('pushWeather')->willThrowException(new \RuntimeException('transport down'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('transport down');

        $this->execute(['from' => '2026-06-26']);
    }

    #[Test]
    public function exceptionsFromRideRetrievalBubbleUp(): void
    {
        $this->rideRetriever = $this->createStub(RideRetrieverInterface::class);
        $this->rideRetriever->method('retrieveRides')->willThrowException(new \RuntimeException('api unreachable'));

        $forecastRetriever = $this->createMock(WeatherForecastRetrieverInterface::class);
        $forecastRetriever->expects(self::never())->method('retrieveWeatherForecastsForRideList');
        $this->forecastRetriever = $forecastRetriever;

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('api unreachable');

        $this->execute(['from' => '2026-06-26']);
    }

    /**
     * @param class-string<ClientExceptionInterface|ServerExceptionInterface> $interface
     */
    private function httpException(string $interface, string $message): \RuntimeException
    {
        $response = $this->createStub(ResponseInterface::class);

        if (ServerExceptionInterface::class === $interface) {
            return new class($message, $response) extends \RuntimeException implements ServerExceptionInterface {
                public function __construct(string $message, private readonly ResponseInterface $response)
                {
                    parent::__construct($message);
                }

                public function getResponse(): ResponseInterface
                {
                    return $this->response;
                }
            };
        }

        return new class($message, $response) extends \RuntimeException implements ClientExceptionInterface {
            public function __construct(string $message, private readonly ResponseInterface $response)
            {
                parent::__construct($message);
            }

            public function getResponse(): ResponseInterface
            {
                return $this->response;
            }
        };
    }
}
