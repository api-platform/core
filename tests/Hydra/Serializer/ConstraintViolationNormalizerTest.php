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
use ApiPlatform\Hydra\Serializer\ConstraintViolationListNormalizer;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Serializer\NameConverter\AdvancedNameConverterInterface;
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

    public function testSupportNormalization(): void
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
     * @dataProvider nameConverterAndPayloadFieldsProvider
     */
    public function testNormalize(?object $nameConverter, ?array $fields, array $expected): void
    {
        $urlGeneratorProphecy = $this->prophesize(UrlGeneratorInterface::class);
        $urlGeneratorProphecy->generate('api_jsonld_context', ['shortName' => 'ConstraintViolationList'])->willReturn('/context/foo')->shouldBeCalled();

        $normalizer = new ConstraintViolationListNormalizer($urlGeneratorProphecy->reveal(), $fields, $nameConverter);

        // Note : we use NotNull constraint and not Constraint class because Constraint is abstract
        $constraint = new NotNull();
        $constraint->payload = ['severity' => 'warning', 'anotherField2' => 'aValue'];
        $list = new ConstraintViolationList([
            new ConstraintViolation('a', 'b', [], 'c', 'd', 'e', null, 'f24bdbad0becef97a6887238aa58221c', $constraint),
            new ConstraintViolation('1', '2', [], '3', '4', '5'),
        ]);

        $this->assertSame($expected, $normalizer->normalize($list));
    }

    public function nameConverterAndPayloadFieldsProvider(): iterable
    {
        $basicExpectation = [
            '@context' => '/context/foo',
            '@type' => 'ConstraintViolationList',
            'hydra:title' => 'An error occurred',
            'hydra:description' => "d: a\n4: 1",
            'violations' => [
                [
                    'propertyPath' => 'd',
                    'message' => 'a',
                    'code' => 'f24bdbad0becef97a6887238aa58221c',
                ],
                [
                    'propertyPath' => '4',
                    'message' => '1',
                    'code' => null,
                ],
            ],
        ];

        $nameConverterBasedExpectation = [
            '@context' => '/context/foo',
            '@type' => 'ConstraintViolationList',
            'hydra:title' => 'An error occurred',
            'hydra:description' => "_d: a\n_4: 1",
            'violations' => [
                [
                    'propertyPath' => '_d',
                    'message' => 'a',
                    'code' => 'f24bdbad0becef97a6887238aa58221c',
                ],
                [
                    'propertyPath' => '_4',
                    'message' => '1',
                    'code' => null,
                ],
            ],
        ];

        $advancedNameConverterProphecy = $this->prophesize(AdvancedNameConverterInterface::class);
        $advancedNameConverterProphecy->normalize(Argument::type('string'), null, Argument::type('string'))->will(fn ($args): string => '_'.$args[0]);
        $advancedNameConverter = $advancedNameConverterProphecy->reveal();

        $nameConverterProphecy = $this->prophesize(NameConverterInterface::class);
        $nameConverterProphecy->normalize(Argument::type('string'))->will(fn ($args): string => '_'.$args[0]);
        $nameConverter = $nameConverterProphecy->reveal();

        $nullNameConverter = null;

        $expected = $nameConverterBasedExpectation;
        $expected['violations'][0]['payload'] = ['severity' => 'warning'];
        yield [$advancedNameConverter, ['severity', 'anotherField1'], $expected];
        yield [$nameConverter, ['severity', 'anotherField1'], $expected];
        $expected = $basicExpectation;
        $expected['violations'][0]['payload'] = ['severity' => 'warning'];
        yield [$nullNameConverter, ['severity', 'anotherField1'], $expected];

        $expected = $nameConverterBasedExpectation;
        $expected['violations'][0]['payload'] = ['severity' => 'warning', 'anotherField2' => 'aValue'];
        yield [$advancedNameConverter, null, $expected];
        yield [$nameConverter, null, $expected];
        $expected = $basicExpectation;
        $expected['violations'][0]['payload'] = ['severity' => 'warning', 'anotherField2' => 'aValue'];
        yield [$nullNameConverter, null, $expected];

        yield [$advancedNameConverter, [], $nameConverterBasedExpectation];
        yield [$nameConverter, [], $nameConverterBasedExpectation];
        yield [$nullNameConverter, [], $basicExpectation];
    }
}
