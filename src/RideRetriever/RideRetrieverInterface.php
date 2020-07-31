<?php declare(strict_types=1);

namespace App\RideRetriever;

interface RideRetrieverInterface
{
    public function retrieveRides(\DateTimeInterface $fromDateTime, \DateTimeInterface $untilDateTime): array;
}
