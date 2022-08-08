<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ApiPlatform\Tests\Hydra\Serializer;

use ApiPlatform\Api\UrlGeneratorInterface;
use ApiPlatform\Hydra\Serializer\ErrorNormalizer;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Symfony\Component\HttpFoundation\Response;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ErrorNormalizerTest extends TestCase
{
    use ProphecyTrait;

    public function testSupportsNormalization(): void
    {
        $urlGeneratorProphecy = $this->prophesize(UrlGeneratorInterface::class);

        $normalizer = new ErrorNormalizer($urlGeneratorProphecy->reveal());

        $this->assertTrue($normalizer->supportsNormalization(new \Exception(), ErrorNormalizer::FORMAT));
        $this->assertFalse($normalizer->supportsNormalization(new \Exception(), 'xml'));
        $this->assertFalse($normalizer->supportsNormalization(new \stdClass(), ErrorNormalizer::FORMAT));

        $this->assertTrue($normalizer->supportsNormalization(new FlattenException(), ErrorNormalizer::FORMAT));
        $this->assertFalse($normalizer->supportsNormalization(new FlattenException(), 'xml'));
        $this->assertFalse($normalizer->supportsNormalization(new \stdClass(), ErrorNormalizer::FORMAT));
        $this->assertTrue($normalizer->hasCacheableSupportsMethod());
    }

    /**
     * @dataProvider providerStatusCode
     *
     * @param int    $status          http status code of the Exception
     * @param string $originalMessage original message of the Exception
     * @param bool   $debug           simulates kernel debug variable
     */
    public function testErrorServerNormalize(int $status, string $originalMessage, bool $debug): void
    {
        $urlGeneratorProphecy = $this->prophesize(UrlGeneratorInterface::class);
        $urlGeneratorProphecy->generate('api_jsonld_context', ['shortName' => 'Error'])->willReturn('/context/foo')->shouldBeCalled();

        $normalizer = new ErrorNormalizer($urlGeneratorProphecy->reveal(), $debug);
        $exception = FlattenException::create(new \Exception($originalMessage), $status);

        $expected = [
            '@context' => '/context/foo',
            '@type' => 'hydra:Error',
            'hydra:title' => 'An error occurred',
            'hydra:description' => ($debug || $status < 500) ? $originalMessage : Response::$statusTexts[$status],
        ];

        if ($debug) {
            $expected['trace'] = $exception->getTrace();
        }

        $this->assertSame($expected, $normalizer->normalize($exception, null, ['statusCode' => $status]));
    }

    public function providerStatusCode(): \Iterator
    {
        yield [Response::HTTP_INTERNAL_SERVER_ERROR, 'Sensitive SQL error displayed', false];
        yield [Response::HTTP_GATEWAY_TIMEOUT, 'Sensitive server error displayed', false];
        yield [Response::HTTP_BAD_REQUEST, 'Bad Request Message', false];
        yield [Response::HTTP_INTERNAL_SERVER_ERROR, 'Sensitive SQL error displayed', true];
        yield [Response::HTTP_GATEWAY_TIMEOUT, 'Sensitive server error displayed', true];
        yield [Response::HTTP_BAD_REQUEST, 'Bad Request Message', true];
    }

    public function testNormalize(): void
    {
        $urlGeneratorProphecy = $this->prophesize(UrlGeneratorInterface::class);
        $urlGeneratorProphecy->generate('api_jsonld_context', ['shortName' => 'Error'])->willReturn('/context/foo')->shouldBeCalled();

        $normalizer = new ErrorNormalizer($urlGeneratorProphecy->reveal());

        $this->assertEquals(
            [
                '@context' => '/context/foo',
                '@type' => 'hydra:Error',
                'hydra:title' => 'An error occurred',
                'hydra:description' => 'Hello',
            ],
            $normalizer->normalize(new \Exception('Hello'))
        );
        $this->assertEquals(
            [
                '@context' => '/context/foo',
                '@type' => 'hydra:Error',
                'hydra:title' => 'Hi',
                'hydra:description' => 'Hello',
            ],
            $normalizer->normalize(new \Exception('Hello'), null, ['title' => 'Hi'])
        );
    }
}
