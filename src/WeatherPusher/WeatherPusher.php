<?php declare(strict_types=1);

namespace App\WeatherPusher;

use App\Entity\Weather;
use App\Serializer\CriticalSerializerInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class WeatherPusher implements WeatherPusherInterface
{
    private HttpClientInterface $client;

    public function __construct(
        private CriticalSerializerInterface $serializer,
        string $criticalmassHostname
    ) {
        $this->client = HttpClient::create([
            'base_uri' => $criticalmassHostname,
            'verify_peer' => false,
            'verify_host' => false,
            'max_redirects' => 20,
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
