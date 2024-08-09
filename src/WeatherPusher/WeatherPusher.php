<?php declare(strict_types=1);

namespace App\WeatherPusher;

use App\Entity\Weather;
use App\Serializer\CriticalSerializerInterface;
use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\Response;

class WeatherPusher implements WeatherPusherInterface
{
    private Client $client;

    public function __construct(
        private CriticalSerializerInterface $serializer,
        string $criticalmassHostname
    ) {
        $this->client = new Client([
            'base_uri' => $criticalmassHostname,
            'verify' => false,
            'allow_redirects' => ['strict' => true]
        ]);
    }

    public function pushWeather(Weather $weather): bool
    {
        $apiUrl = sprintf('/api/%s/%s/weather', $weather->getRide()->getCity()->getMainSlug()->getSlug(), $weather->getRide()->getDateTime()->format('Y-m-d'));

        $response = $this->client->request('PUT', $apiUrl, [
            'body' => $this->serializer->serialize($weather, 'json'),
        ]);

        return Response::HTTP_CREATED === $response->getStatusCode();
    }
}
