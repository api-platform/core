<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Tests\JsonLd\Serializer;

use Dunglas\ApiBundle\JsonLd\Serializer\DateTimeNormalizer;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;

/**
 * @author Théo FIDRY <theo.fidry@gmail.com>
 */
class DateTimeNormalizerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DateTimeNormalizer
     */
    private $normalizer;

    public function setUp()
    {
        $this->normalizer = new DateTimeNormalizer();
    }

    public function testNormalize()
    {
        $date = new \DateTime('2015-05-05T00:00:00', new \DateTimeZone('UTC'));

        $this->assertEquals(
            '2015-05-05T00:00:00+00:00',
            $this->normalizer->normalize($date)
        );

        $this->assertEquals(
            '2015-05-05T00:00:00+00:00',
            $this->normalizer->normalize($date, 'any')
        );
    }

    public function testDenormalize()
    {
        $dateTime = $this->normalizer->denormalize('2015-05-05T00:00:00+00:00', '\DateTime');
        $this->assertEquals($dateTime->format(\DateTime::ATOM), '2015-05-05T00:00:00+00:00');

        $dateTime = $this->normalizer->denormalize('2015-05-05T00:00:00+00:00', 'DateTime');
        $this->assertEquals($dateTime->format(\DateTime::ATOM), '2015-05-05T00:00:00+00:00');

        $dateTime = $this->normalizer->denormalize(
            [
                'date'          => '2015-05-05 10:50:13.1234',
                'timezone_type' => 3,
                'timezone'      => 'Europe/Zurich',
            ],
            '\DateTime'
        );
        $this->assertInstanceOf('\DateTime', $dateTime);
        $this->assertEquals(
            \DateTime::createFromFormat(
                'Y-m-d H:i:s.u',
                '2015-05-05 10:50:13.1234',
                new \DateTimeZone('Europe/Zurich')
            ),
            $dateTime
        );

        try {
            $this->normalizer->denormalize(
                [
                    'date'          => '2015-05-05 10:50:13.1234',
                    'timezone_type' => 3,
                    'timezone'      => 'azerty',
                ],
                '\DateTime'
            );
            $this->fail('Expected exception to be thrown');
        } catch (InvalidArgumentException $exception) {
            $this->assertEquals(
                'DateTimeZone::__construct(): Unknown or bad timezone (azerty)',
                $exception->getMessage()
            );
        }

        try {
            $this->normalizer->denormalize(
                [
                    'date'          => '2015-05-05 10:50:13+00:00',
                    'timezone_type' => 3,
                    'timezone'      => 'Europe/Paris',
                ],
                '\DateTime'
            );
            $this->fail('Expected exception to be thrown');
        } catch (InvalidArgumentException $exception) {
            $this->assertEquals('Unexpected data found. Trailing data', $exception->getMessage());
        }

        try {
            $this->normalizer->denormalize(
                [
                    'date'          => '2015-05-05T10:50:13',
                    'timezone_type' => 3,
                    'timezone'      => 'Europe/Paris',
                ],
                '\DateTime'
            );
            $this->fail('Expected exception to be thrown');
        } catch (InvalidArgumentException $exception) {
            $this->assertEquals('Unexpected data found. Data missing', $exception->getMessage());
        }
    }

    public function testSupportsNormalization()
    {
        $this->assertTrue($this->normalizer->supportsNormalization(new \DateTime(), 'jsonld'));
        $this->assertTrue($this->normalizer->supportsNormalization(new \DateTime(), 'jsonld'));

        $this->assertFalse($this->normalizer->supportsNormalization(new \DateTime()));
        $this->assertFalse($this->normalizer->supportsNormalization(new \DateTime()), 'any');


        $this->assertFalse($this->normalizer->supportsNormalization('string'));
        $this->assertFalse($this->normalizer->supportsNormalization(new \stdClass()));
    }

    public function testSupportsDenormalization()
    {
        $this->assertTrue($this->normalizer->supportsDenormalization('string', 'DateTime', 'jsonld'));
        $this->assertTrue($this->normalizer->supportsDenormalization('string', '\DateTime', 'jsonld'));

        $this->assertFalse($this->normalizer->supportsDenormalization('string', 'DateTime'));
        $this->assertFalse($this->normalizer->supportsDenormalization('string', '\DateTime', 'any'));


        $this->assertTrue(
            $this->normalizer->supportsDenormalization(
                [
                    'date'     => true,
                    'timezone' => true,
                ],
                'DateTime',
                'jsonld'
            )
        );
        $this->assertTrue(
            $this->normalizer->supportsDenormalization(
                [
                    'date'     => true,
                    'timezone' => true,
                ],
                '\DateTime',
                'jsonld'
            )
        );

        $this->assertFalse(
            $this->normalizer->supportsDenormalization(
                [
                    'date'     => true,
                    'timezone' => true,
                ],
                'DateTime'
            )
        );
        $this->assertFalse(
            $this->normalizer->supportsDenormalization(
                [
                    'date'     => true,
                    'timezone' => true,
                ],
                '\DateTime',
                'any'
            )
        );
    }
}
