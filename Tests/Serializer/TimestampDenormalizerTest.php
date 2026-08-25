<?php declare(strict_types=1);

namespace App\Tests\Serializer;

use App\Serializer\TimestampDenormalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class TimestampDenormalizerTest extends TestCase
{
    private TimestampDenormalizer $denormalizer;

    protected function setUp(): void
    {
        $this->denormalizer = new TimestampDenormalizer();
    }

    #[Test]
    public function supportsIntegerTimestampsForDateTime(): void
    {
        self::assertTrue($this->denormalizer->supportsDenormalization(1782493200, \DateTime::class));
        self::assertTrue($this->denormalizer->supportsDenormalization(0, \DateTime::class, 'json'));
        self::assertTrue($this->denormalizer->supportsDenormalization(-1, \DateTime::class));
    }

    /**
     * @return iterable<string, array{mixed, string}>
     */
    public static function unsupportedCombinations(): iterable
    {
        yield 'numeric string' => ['1782493200', \DateTime::class];
        yield 'float' => [1782493200.0, \DateTime::class];
        yield 'null' => [null, \DateTime::class];
        yield 'iso string' => ['2026-06-26T19:00:00+02:00', \DateTime::class];
        yield 'DateTimeImmutable type' => [1782493200, \DateTimeImmutable::class];
        yield 'DateTimeInterface type' => [1782493200, \DateTimeInterface::class];
        yield 'string type' => [1782493200, 'string'];
    }

    #[Test]
    #[DataProvider('unsupportedCombinations')]
    public function doesNotSupportOtherDataOrTypes(mixed $data, string $type): void
    {
        self::assertFalse($this->denormalizer->supportsDenormalization($data, $type));
    }

    #[Test]
    public function denormalizesIntegerToDateTimeWithThatTimestamp(): void
    {
        $result = $this->denormalizer->denormalize(1782493200, \DateTime::class);

        self::assertInstanceOf(\DateTime::class, $result);
        self::assertSame(1782493200, $result->getTimestamp());
        self::assertSame('2026-06-26T17:00:00+00:00', $result->setTimezone(new \DateTimeZone('UTC'))->format(\DateTimeInterface::ATOM));
    }

    #[Test]
    public function denormalizesEpochZero(): void
    {
        self::assertSame(0, $this->denormalizer->denormalize(0, \DateTime::class)->getTimestamp());
    }

    #[Test]
    public function eachCallReturnsAFreshInstance(): void
    {
        $first = $this->denormalizer->denormalize(1, \DateTime::class);
        $second = $this->denormalizer->denormalize(1, \DateTime::class);

        self::assertNotSame($first, $second);
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function invalidTimestamps(): iterable
    {
        yield 'numeric string' => ['1782493200'];
        yield 'float' => [1.5];
        yield 'null' => [null];
        yield 'array' => [[1]];
    }

    #[Test]
    #[DataProvider('invalidTimestamps')]
    public function denormalizeRejectsNonIntegers(mixed $data): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid timestamp');

        $this->denormalizer->denormalize($data, \DateTime::class);
    }

    #[Test]
    public function announcesOnlyDateTimeAsSupportedType(): void
    {
        self::assertSame([\DateTime::class], $this->denormalizer->getSupportedTypes('json'));
        self::assertSame([\DateTime::class], $this->denormalizer->getSupportedTypes(null));
    }
}
