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
            'max_redirects' => 3,
        ]);
    }

    public function pushWeather(Weather $weather): bool
    {
        // The city slug comes from the remote API response, so it is untrusted
        // input for the URL we are about to call back. Percent-encode it: an
        // unencoded "../" would let a manipulated response steer this PUT - and
        // its serialized payload - at a different endpoint of the API host.
        $apiUrl = sprintf(
            '/api/%s/%s/weather',
            rawurlencode($weather->getRide()->getCity()->getMainSlug()->getSlug()),
            $weather->getRide()->getDateTime()->format('Y-m-d')
        );

        $response = $this->client->request('PUT', $apiUrl, [
            'body' => $this->serializer->serialize($weather, 'json'),
        ]);

        return Response::HTTP_CREATED === $response->getStatusCode();
    }
}
