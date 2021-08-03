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

namespace ApiPlatform\Core\Tests\Metadata\Resource\Factory;

use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Metadata\Resource\Factory\FormatsResourceMetadataFactory;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Tests\ProphecyTrait;
use PHPUnit\Framework\TestCase;

class FormatsResourceMetadataFactoryTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @dataProvider createProvider
     */
    public function testCreate(ResourceMetadata $previous, ResourceMetadata $expected, array $formats = [], array $patchFormats = []): void
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn($previous);

        $actual = (new FormatsResourceMetadataFactory($resourceMetadataFactoryProphecy->reveal(), $formats, $patchFormats))->create('Foo');
        $this->assertEquals($expected, $actual);
    }

    public function createProvider(): iterable
    {
        yield [
            new ResourceMetadata(
                null,
                null,
                null,
                ['get' => []],
                ['get' => []],
                ['formats' => 'json'],
                ['get' => []]
            ),
            new ResourceMetadata(
                null,
                null,
                null,
                ['get' => ['input_formats' => ['json' => ['application/json']], 'output_formats' => ['json' => ['application/json']]]],
                ['get' => ['input_formats' => ['json' => ['application/json']], 'output_formats' => ['json' => ['application/json']]]],
                ['formats' => 'json'],
                ['get' => ['input_formats' => ['json' => ['application/json']], 'output_formats' => ['json' => ['application/json']]]]
            ),
            ['json' => ['application/json']],
        ];

        yield [
            new ResourceMetadata(
                null,
                null,
                null,
                ['get' => []],
                ['get' => []],
                ['formats' => ['json' => ['application/json']]],
                ['get' => []]
            ),
            new ResourceMetadata(
                null,
                null,
                null,
                ['get' => ['input_formats' => ['json' => ['application/json']], 'output_formats' => ['json' => ['application/json']]]],
                ['get' => ['input_formats' => ['json' => ['application/json']], 'output_formats' => ['json' => ['application/json']]]],
                ['formats' => ['json' => ['application/json']]],
                ['get' => ['input_formats' => ['json' => ['application/json']], 'output_formats' => ['json' => ['application/json']]]]
            ),
        ];

        yield [
            new ResourceMetadata(
                null,
                null,
                null,
                ['get' => []],
                ['get' => []],
                ['input_formats' => ['json' => ['application/json'], 'xml' => ['text/xml', 'application/xml']], 'output_formats' => ['csv' => 'text/csv']],
                ['get' => []]
            ),
            new ResourceMetadata(
                null,
                null,
                null,
                ['get' => ['input_formats' => ['json' => ['application/json'], 'xml' => ['text/xml', 'application/xml']], 'output_formats' => ['csv' => ['text/csv']]]],
                ['get' => ['input_formats' => ['json' => ['application/json'], 'xml' => ['text/xml', 'application/xml']], 'output_formats' => ['csv' => ['text/csv']]]],
                ['input_formats' => ['json' => ['application/json'], 'xml' => ['text/xml', 'application/xml']], 'output_formats' => ['csv' => 'text/csv']],
                ['get' => ['input_formats' => ['json' => ['application/json'], 'xml' => ['text/xml', 'application/xml']], 'output_formats' => ['csv' => ['text/csv']]]]
            ),
        ];

        yield [
            new ResourceMetadata(
                null,
                null,
                null,
                ['patch' => ['method' => 'PATCH']]
            ),
            new ResourceMetadata(
                null,
                null,
                null,
                ['patch' => ['method' => 'PATCH', 'input_formats' => ['json' => ['application/merge-patch+json']], 'output_formats' => []]]
            ),
            [],
            ['json' => ['application/merge-patch+json']],
        ];

        yield [
            new ResourceMetadata(
                null,
                null,
                null,
                ['get' => ['formats' => 'json']]
            ),
            new ResourceMetadata(
                null,
                null,
                null,
                ['get' => ['formats' => ['json' => ['application/json']], 'input_formats' => ['json' => ['application/json']], 'output_formats' => ['json' => ['application/json']]]]
            ),
            ['json' => ['application/json']],
        ];

        yield [
            new ResourceMetadata(
                null,
                null,
                null,
                ['get' => ['input_formats' => 'json', 'output_formats' => 'json']]
            ),
            new ResourceMetadata(
                null,
                null,
                null,
                ['get' => ['input_formats' => ['json' => ['application/json']], 'output_formats' => ['json' => ['application/json']]]]
            ),
            ['json' => ['application/json']],
        ];
    }

    public function testInvalidFormatType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The \'formats\' attributes value must be a string when trying to include an already configured format, object given.');

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceMetadata(
            null,
            null,
            null,
            null,
            null,
            ['formats' => [new \stdClass()]]
        ));

        (new FormatsResourceMetadataFactory($resourceMetadataFactoryProphecy->reveal(), [], []))->create('Foo');
    }

    public function testNotConfiguredFormat(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('You either need to add the format \'xml\' to your project configuration or declare a mime type for it in your annotation.');

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceMetadata(
            null,
            null,
            null,
            null,
            null,
            ['formats' => ['xml']]
        ));

        (new FormatsResourceMetadataFactory($resourceMetadataFactoryProphecy->reveal(), [], []))->create('Foo');
    }
}
