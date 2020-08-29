<?php declare(strict_types=1);

namespace App\WeatherPusher;

use App\Entity\Weather;
use GuzzleHttp\Client;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Response;

class WeatherPusher implements WeatherPusherInterface
{
    protected Client $client;
    protected SerializerInterface $serializer;

    public function __construct(SerializerInterface $serializer, string $criticalmassHostname)
    {
        $this->client = new Client([
            'base_uri' => $criticalmassHostname,
            'verify' => false,
        ]);

        $this->serializer = $serializer;
    }

    public function pushWeather(Weather $weather): bool
    {
        $apiUrl = sprintf('/api/%s/%s/weather', $weather->getRide()->getCity()->getMainSlug()->getSlug(), $weather->getRide()->getDateTime()->format('Y-m-d'));

        $response = $this->client->put($apiUrl, [
            'body' => $this->serializer->serialize($weather, 'json'),
        ]);

        return Response::HTTP_CREATED === $response->getStatusCode();
    }
}