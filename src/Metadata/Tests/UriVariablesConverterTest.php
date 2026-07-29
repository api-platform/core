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

namespace ApiPlatform\Metadata\Tests;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\UriVariablesConverter;
use ApiPlatform\Metadata\UriVariableTransformerInterface;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\Dummy;
use PHPUnit\Framework\TestCase;
use Symfony\Component\TypeInfo\Type\BuiltinType;
use Symfony\Component\TypeInfo\TypeIdentifier;

class UriVariablesConverterTest extends TestCase
{
    public function testParameterNameAddedToContext(): void
    {
        $transformer = $this->createMock(UriVariableTransformerInterface::class);

        $contextDuringSupports = [];
        $transformer
            ->expects($this->once())
            ->method('supportsTransformation')
            ->willReturnCallback(static function ($v, $t, $context) use (&$contextDuringSupports) {
                $contextDuringSupports = $context;

                return true;
            });

        $contextduringTransform = [];
        $transformer
            ->expects($this->once())
            ->method('transform')
            ->willReturnCallback(static function ($v, $t, $context) use (&$contextduringTransform) {
                $contextduringTransform = $context;

                return $v;
            });

        $metadataFactory = $this->createStub(PropertyMetadataFactoryInterface::class);
        $metadataFactory->method('create')->willReturn(new ApiProperty(nativeType: new BuiltinType(TypeIdentifier::STRING)));

        $converter = new UriVariablesConverter(
            $metadataFactory,
            $this->createStub(ResourceMetadataCollectionFactoryInterface::class),
            [$transformer],
        );

        $context = ['operation' => new Get(), 'parameterName' => 'not-overwritten'];
        $converter->convert(['foo' => 'bar'], Dummy::class, $context);

        self::assertArrayHasKey('parameterName', $contextDuringSupports);
        self::assertEquals($contextDuringSupports['parameterName'], 'foo');

        self::assertArrayHasKey('parameterName', $contextduringTransform);
        self::assertEquals($contextDuringSupports['parameterName'], 'foo');

        self::assertEquals('not-overwritten', $context['parameterName']);
    }
}
