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

namespace ApiPlatform\Core\Tests\Hydra\Serializer;

use ApiPlatform\Core\Api\UrlGeneratorInterface;
use ApiPlatform\Core\Hydra\Serializer\ErrorNormalizer;
use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\HttpFoundation\Response;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ErrorNormalizerTest extends \PHPUnit_Framework_TestCase
{
    public function testSupportNormalization()
    {
        $urlGeneratorProphecy = $this->prophesize(UrlGeneratorInterface::class);

        $normalizer = new ErrorNormalizer($urlGeneratorProphecy->reveal());

        $this->assertTrue($normalizer->supportsNormalization(new \Exception(), ErrorNormalizer::FORMAT));
        $this->assertFalse($normalizer->supportsNormalization(new \Exception(), 'xml'));
        $this->assertFalse($normalizer->supportsNormalization(new \stdClass(), ErrorNormalizer::FORMAT));

        $this->assertTrue($normalizer->supportsNormalization(new FlattenException(), ErrorNormalizer::FORMAT));
        $this->assertFalse($normalizer->supportsNormalization(new FlattenException(), 'xml'));
        $this->assertFalse($normalizer->supportsNormalization(new \stdClass(), ErrorNormalizer::FORMAT));
    }

    /**
     * @dataProvider providerStatusCode
     *
     * @param $status            http status code of the Exception
     * @param $originalMessage   original message of the Exception
     * @param $debug             simulates kernel debug variable
     */
    public function testErrorServerNormalize($status, $originalMessage, $debug)
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

        $this->assertEquals($expected, $normalizer->normalize($exception, null, ['statusCode' => $status]));
    }

    public function providerStatusCode()
    {
        return [
            [Response::HTTP_INTERNAL_SERVER_ERROR, 'Sensitive SQL error displayed', false],
            [Response::HTTP_GATEWAY_TIMEOUT, 'Sensitive server error displayed', false],
            [Response::HTTP_BAD_REQUEST, 'Bad Request Message', false],
            [Response::HTTP_INTERNAL_SERVER_ERROR, 'Sensitive SQL error displayed', true],
            [Response::HTTP_GATEWAY_TIMEOUT, 'Sensitive server error displayed', true],
            [Response::HTTP_BAD_REQUEST, 'Bad Request Message', true],
        ];
    }

    public function testNormalize()
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
