<?php declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\UpdateWeatherCommand;
use App\ForecastRetriever\WeatherForecastRetriever;
use App\ForecastRetriever\WeatherForecastRetrieverInterface;
use App\RideRetriever\RideRetriever;
use App\RideRetriever\RideRetrieverInterface;
use App\Serializer\CriticalSerializer;
use App\Serializer\CriticalSerializerInterface;
use App\WeatherPusher\WeatherPusher;
use App\WeatherPusher\WeatherPusherInterface;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\LazyCommand;

/**
 * Boots the real kernel to prove the service wiring (interface aliases, env bindings)
 * resolves. No command is executed, so no HTTP client is ever used.
 */
final class UpdateWeatherCommandKernelTest extends KernelTestCase
{
    #[Test]
    public function commandIsRegisteredInTheConsoleApplication(): void
    {
        $application = new Application(self::bootKernel());

        $command = $application->find('criticalmass:weather:update');

        self::assertInstanceOf(LazyCommand::class, $command);
        self::assertInstanceOf(UpdateWeatherCommand::class, $command->getCommand());
        self::assertSame('Retrieve weather forecasts for parameterized range', $command->getDescription());
        self::assertSame(['from', 'until'], array_keys($command->getDefinition()->getArguments()));
    }

    #[Test]
    public function interfacesAreAutowiredToTheirSingleImplementations(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        self::assertInstanceOf(RideRetriever::class, $container->get(RideRetrieverInterface::class));
        self::assertInstanceOf(WeatherForecastRetriever::class, $container->get(WeatherForecastRetrieverInterface::class));
        self::assertInstanceOf(WeatherPusher::class, $container->get(WeatherPusherInterface::class));
        self::assertInstanceOf(CriticalSerializer::class, $container->get(CriticalSerializerInterface::class));
    }

    #[Test]
    public function commandReceivesTheContainerServices(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $command = $container->get(UpdateWeatherCommand::class);

        $reflection = new \ReflectionClass(UpdateWeatherCommand::class);
        self::assertSame($container->get(RideRetrieverInterface::class), $reflection->getProperty('rideRetriever')->getValue($command));
        self::assertSame($container->get(WeatherForecastRetrieverInterface::class), $reflection->getProperty('weatherForecastRetriever')->getValue($command));
        self::assertSame($container->get(WeatherPusherInterface::class), $reflection->getProperty('weatherPusher')->getValue($command));
    }
}
