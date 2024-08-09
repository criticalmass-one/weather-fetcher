<?php declare(strict_types=1);

namespace App\RideRetriever;

use App\Entity\Ride;
use App\Serializer\CriticalSerializerInterface;
use GuzzleHttp\Client;

class RideRetriever implements RideRetrieverInterface
{
    private Client $client;

    public function __construct(
        private readonly CriticalSerializerInterface $serializer,
        string $criticalmassHostname
    )
    {
        $this->client = new Client([
            'base_uri' => $criticalmassHostname,
            'verify' => false,
        ]);
    }

    public function retrieveRides(\DateTimeInterface $fromDateTime, \DateTimeInterface $untilDateTime): array
    {
        $dayInterval = new \DateInterval('P1D');
        $rideList = [];
        $dateTime = $fromDateTime;

        do {
            $rideList += $this->retrieveRidesForDate($dateTime);

            $dateTime = $dateTime->add($dayInterval);
        } while ($dateTime < $untilDateTime);

        return $rideList;
    }

    protected function retrieveRidesForDate(\DateTimeInterface $dateTime): array
    {
        $parameters = [
            'year' => $dateTime->format('Y'),
            'month' => $dateTime->format('m'),
            'day' => $dateTime->format('d'),
            'size' => 250,
            'extended' => true,
        ];

        $queryString = sprintf('/api/ride?%s', http_build_query($parameters));

        $response = $this->client->request('GET', $queryString);

        $rawResponse = $response->getBody()->getContents();

        return $this->serializer->deserialize($rawResponse, sprintf('%s[]', Ride::class), 'json');
    }
}
