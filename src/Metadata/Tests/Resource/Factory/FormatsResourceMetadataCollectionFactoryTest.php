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

namespace ApiPlatform\Metadata\Tests\Resource\Factory;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Resource\Factory\FormatsResourceMetadataCollectionFactory;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class FormatsResourceMetadataCollectionFactoryTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @dataProvider createProvider
     */
    public function testCreate(ApiResource $previous, ApiResource $expected, array $formats = [], array $patchFormats = []): void
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceMetadataCollection('Foo', [$previous]));

        $actual = (new FormatsResourceMetadataCollectionFactory($resourceMetadataFactoryProphecy->reveal(), $formats, $patchFormats))->create('Foo')[0];
        $this->assertEquals($expected, $actual);
    }

    public static function createProvider(): iterable
    {
        yield [
            new ApiResource(
                formats: 'json',
                operations: ['get' => new Get()]
            ),
            new ApiResource(
                formats: 'json',
                operations: ['get' => new Get(inputFormats: ['json' => ['application/json']], outputFormats: ['json' => ['application/json']])]
            ),
            ['json' => ['application/json']],
        ];

        yield [
            new ApiResource(
                formats: ['json' => ['application/json']],
                operations: ['get' => new Get()]
            ),
            new ApiResource(
                formats: ['json' => ['application/json']],
                operations: ['get' => new Get(inputFormats: ['json' => ['application/json']], outputFormats: ['json' => ['application/json']])]
            ),
        ];

        yield [
            new ApiResource(
                inputFormats: ['json' => ['application/json'], 'xml' => ['text/xml', 'application/xml']],
                outputFormats: ['csv' => ['text/csv']],
                operations: ['get' => new Get()]
            ),
            new ApiResource(
                inputFormats: ['json' => ['application/json'], 'xml' => ['text/xml', 'application/xml']],
                outputFormats: ['csv' => ['text/csv']],
                operations: ['get' => new Get(
                    inputFormats: ['json' => ['application/json'], 'xml' => ['text/xml', 'application/xml']],
                    outputFormats: ['csv' => ['text/csv']],
                )]
            ),
        ];

        yield [
            new ApiResource(
                operations: [
                    'patch' => new Patch(),
                ]
            ),
            new ApiResource(
                operations: [
                    'patch' => new Patch(inputFormats: ['json' => ['application/merge-patch+json']], acceptPatch: 'application/merge-patch+json', outputFormats: []),
                ]
            ),
            [],
            ['json' => ['application/merge-patch+json']],
        ];

        yield [
            new ApiResource(
                operations: ['get' => new Get(formats: 'json')]
            ),
            new ApiResource(
                operations: ['get' => new Get(formats: ['json' => ['application/json']], inputFormats: ['json' => ['application/json']], outputFormats: ['json' => ['application/json']])]
            ),
            ['json' => ['application/json']],
        ];

        yield [
            new ApiResource(
                operations: ['get' => new Get(inputFormats: 'json', outputFormats: 'json')]
            ),
            new ApiResource(
                operations: ['get' => new Get(inputFormats: ['json' => ['application/json']], outputFormats: ['json' => ['application/json']])]
            ),
            ['json' => ['application/json']],
        ];
    }

    public function testInvalidFormatType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The \'formats\' attributes value must be a string when trying to include an already configured format, object given.');

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceMetadataCollection('Foo', [new ApiResource(formats: [new \stdClass()])]));

        (new FormatsResourceMetadataCollectionFactory($resourceMetadataFactoryProphecy->reveal(), [], []))->create('Foo');
    }

    public function testNotConfiguredFormat(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('You either need to add the format \'xml\' to your project configuration or declare a mime type for it in your annotation.');

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceMetadataCollection('Foo', [new ApiResource(formats: ['xml'])]));

        (new FormatsResourceMetadataCollectionFactory($resourceMetadataFactoryProphecy->reveal(), [], []))->create('Foo');
    }
}
