<?php declare(strict_types=1);

namespace App\Controller\Api;

use App\RideRetriever\RideRetrieverInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class RideApiController extends AbstractController
{
    #[Route('/api/rides', name: 'api_rides', methods: ['GET'])]
    public function list(RideRetrieverInterface $rideRetriever): JsonResponse
    {
        $from = new \DateTime('today');
        $until = (new \DateTime('today'))->modify('+5 days');

        $rides = $rideRetriever->retrieveRides($from, $until);

        $data = array_map(function ($ride) {
            return [
                'title' => $ride->getTitle(),
                'city' => $ride->getCity()->getName(),
                'citySlug' => $ride->getCity()->getMainSlug()->getSlug(),
                'dateTime' => $ride->getDateTime()->format('c'),
                'date' => $ride->getDateTime()->format('Y-m-d'),
                'time' => $ride->getDateTime()->format('H:i'),
                'location' => $ride->getLocation(),
                'latitude' => $ride->getLatitude(),
                'longitude' => $ride->getLongitude(),
                'hasCoordinates' => $ride->getLatitude() !== null && $ride->getLongitude() !== null,
            ];
        }, $rides);

        return $this->json($data);
    }
}
