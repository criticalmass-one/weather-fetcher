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

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
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

        dd($rideList);

        $this->weatherForecastRetriever->retrieve($startDateTime, $endDateTime);

        $newForecasts = $this->weatherForecastRetriever->getNewWeatherForecasts();

        $table = new Table($output);
        $table
            ->setHeaders(['City', 'DateTime']);

        /** @var Weather $weather */
        foreach ($newForecasts as $weather) {
            $table
                ->addRow([
                    $weather->getRide()->getCity()->getCity(),
                    $weather->getRide()->getDateTime()->format('Y-m-d'),
                ]);
        }

        $table->render();
    }
}
