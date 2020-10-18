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
use ApiPlatform\Core\Hydra\Serializer\ConstraintViolationListNormalizer;
use ApiPlatform\Core\Tests\ProphecyTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ConstraintViolationNormalizerTest extends TestCase
{
    use ProphecyTrait;

    public function testSupportNormalization()
    {
        $urlGeneratorProphecy = $this->prophesize(UrlGeneratorInterface::class);
        $nameConverterProphecy = $this->prophesize(NameConverterInterface::class);

        $normalizer = new ConstraintViolationListNormalizer($urlGeneratorProphecy->reveal(), [], $nameConverterProphecy->reveal());

        $this->assertTrue($normalizer->supportsNormalization(new ConstraintViolationList(), ConstraintViolationListNormalizer::FORMAT));
        $this->assertFalse($normalizer->supportsNormalization(new ConstraintViolationList(), 'xml'));
        $this->assertFalse($normalizer->supportsNormalization(new \stdClass(), ConstraintViolationListNormalizer::FORMAT));
        $this->assertTrue($normalizer->hasCacheableSupportsMethod());
    }

    /**
     * @dataProvider payloadFieldsProvider
     */
    public function testNormalize(?array $fields, array $result)
    {
        $urlGeneratorProphecy = $this->prophesize(UrlGeneratorInterface::class);
        $nameConverterProphecy = $this->prophesize(NameConverterInterface::class);

        $urlGeneratorProphecy->generate('api_jsonld_context', ['shortName' => 'ConstraintViolationList'])->willReturn('/context/foo')->shouldBeCalled();
        $nameConverterProphecy->normalize(Argument::type('string'), null, Argument::type('string'))->will(function ($args) {
            return '_'.$args[0];
        });

        $normalizer = new ConstraintViolationListNormalizer($urlGeneratorProphecy->reveal(), $fields, $nameConverterProphecy->reveal());

        // Note : we use NotNull constraint and not Constraint class because Constraint is abstract
        $constraint = new NotNull();
        $constraint->payload = ['severity' => 'warning', 'anotherField2' => 'aValue'];
        $list = new ConstraintViolationList([
            new ConstraintViolation('a', 'b', [], 'c', 'd', 'e', null, null, $constraint),
            new ConstraintViolation('1', '2', [], '3', '4', '5'),
        ]);

        $expected = [
            '@context' => '/context/foo',
            '@type' => 'ConstraintViolationList',
            'hydra:title' => 'An error occurred',
            'hydra:description' => "_d: a\n_4: 1",
            'violations' => [
                [
                    'propertyPath' => '_d',
                    'message' => 'a',
                ],
                [
                    'propertyPath' => '_4',
                    'message' => '1',
                ],
            ],
        ];
        if ([] !== $result) {
            $expected['violations'][0]['payload'] = $result;
        }

        $this->assertEquals($expected, $normalizer->normalize($list));
    }

    public function payloadFieldsProvider(): iterable
    {
        yield [['severity', 'anotherField1'], ['severity' => 'warning']];
        yield [null, ['severity' => 'warning', 'anotherField2' => 'aValue']];
        yield [[], []];
    }
}
