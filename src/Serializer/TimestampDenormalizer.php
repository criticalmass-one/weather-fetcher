<?php declare(strict_types=1);

namespace App\Serializer;

use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class TimestampDenormalizer implements DenormalizerInterface
{
    public function denormalize($data, $type, $format = null, array $context = []): \DateTime
    {
        if (is_int($data)) {
            return (new \DateTime())->setTimestamp($data);
        }

        throw new \InvalidArgumentException('Invalid timestamp');
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return $type === \DateTime::class && is_int($data);
    }

    public function getSupportedTypes(?string $format): array
    {
        return [\DateTime::class];
    }
}
