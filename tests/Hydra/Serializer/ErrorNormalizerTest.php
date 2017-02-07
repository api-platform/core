<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Tests\Hydra\Serializer;

use ApiPlatform\Core\Api\UrlGeneratorInterface;
use ApiPlatform\Core\Hydra\Serializer\ErrorNormalizer;
use Symfony\Component\Debug\Exception\FlattenException;

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
