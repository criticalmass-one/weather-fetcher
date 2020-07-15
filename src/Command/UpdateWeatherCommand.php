<?php declare(strict_types=1);

namespace App\Command;

use App\Entity\Weather;
use App\ForecastRetriever\WeatherForecastRetrieverInterface;
use App\RideRetriever\RideRetrieverInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class UpdateWeatherCommand extends Command
{
    protected WeatherForecastRetrieverInterface $weatherForecastRetriever;
    protected RideRetrieverInterface $rideRetriever;

    public function __construct(RideRetrieverInterface $rideRetriever, WeatherForecastRetrieverInterface $weatherForecastRetriever)
    {
        $this->weatherForecastRetriever = $weatherForecastRetriever;
        $this->rideRetriever = $rideRetriever;

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

        $weatherList = $this->weatherForecastRetriever->retrieveWeatherForecastsForRideList($rideList);

        $table = new Table($output);
        $table->setHeaders(['City', 'DateTime']);

        /** @var Weather $weather */
        foreach ($weatherList as $weather) {
            $table
                ->addRow([
              //      $weather->getRide()->getCity()->getCity(),
                    $weather->getWeatherDateTime()->format('Y-m-d'),
                ]);
        }

        $table->render();

        return Command::SUCCESS;
    }
}
