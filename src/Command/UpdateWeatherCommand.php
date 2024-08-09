<?php declare(strict_types=1);

namespace App\Command;

use App\Entity\Ride;
use App\Entity\Weather;
use App\ForecastRetriever\WeatherForecastRetrieverInterface;
use App\RideRetriever\RideRetrieverInterface;
use App\WeatherPusher\WeatherPusherInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class UpdateWeatherCommand extends Command
{
    public function __construct(
        private readonly RideRetrieverInterface $rideRetriever,
        private readonly WeatherForecastRetrieverInterface $weatherForecastRetriever,
        private readonly WeatherPusherInterface $weatherPusher
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('criticalmass:weather:update')
            ->setDescription('Retrieve weather forecasts for parameterized range')
            ->addArgument(
                'from',
                InputArgument::OPTIONAL,
                'Range start date time'
            )
            ->addArgument(
                'until',
                InputArgument::OPTIONAL,
                'Range end date time'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($input->getArgument('from')) {
            $startDateTime = new \DateTimeImmutable($input->getArgument('from'));
        } else {
            $startDateTime = new \DateTimeImmutable();
        }

        if ($input->getArgument('until')) {
            $endDateTime = new \DateTimeImmutable($input->getArgument('until'));
        } else {
            $period = new \DateInterval('P1W');
            $endDateTime = $startDateTime->add($period);
        }

        $rideList = $this->rideRetriever->retrieveRides($startDateTime, $endDateTime);

        $io->success(sprintf('Retrieved %d rides from %s until %s', count($rideList), $startDateTime->format('Y-m-d'), $endDateTime->format('Y-m-d')));

        $io->table([
            'City',
            'DateTime',
            'Title',
            'Location',
            'Latitude',
            'Longitude',
        ], array_map(function (Ride $ride): array
        {
            return [
                $ride->getCity()->getName(),
                $ride->getDateTime()->format('Y-m-d H-i-s'),
                $ride->getTitle(),
                $ride->getLocation(),
                $ride->getLatitude(),
                $ride->getLongitude(),
            ];
        }, $rideList));

        $weatherList = $this->weatherForecastRetriever->retrieveWeatherForecastsForRideList($rideList);

        $io->success(sprintf('Retrieved %d weather data items for %d rides', count($weatherList), count($rideList)));

        $io->table([
            'City',
            'Weather DateTime',
            'Weather Description'
        ], array_map(function (Weather $weather): array
        {
            return [
                $weather->getRide()->getCity()->getName(),
                $weather->getWeatherDateTime()->format('Y-m-d H-i-s'),
                $weather->getWeatherDescription(),
            ];
        }, $weatherList));

        $successCounter = 0;

        foreach ($weatherList as $weather) {
            try {
                $result = $this->weatherPusher->pushWeather($weather);

                if ($result) {
                    ++$successCounter;
                }
            } catch (ServerException $exception) {

            } catch (ClientException $clientException) {

            }
        }

        if ($successCounter > 0) {
            $io->success(sprintf('Pushed %d weather data items to api', $successCounter));
        }

        if (count($weatherList) - $successCounter > 0) {
            $io->error(sprintf('Could not push %d weather data items to api', count($weatherList) - $successCounter));
        }

        return Command::SUCCESS;
    }
}
