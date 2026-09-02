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

namespace ApiPlatform\Metadata\Tests\Resource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Error;
use ApiPlatform\Metadata\ErrorResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use ApiPlatform\Metadata\GraphQl\Subscription;
use ApiPlatform\Metadata\Metadata;
use ApiPlatform\Metadata\NotExposed;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Tests\Fixtures\Metadata\RestfulApi;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class StringableDescriptionParameterTest extends TestCase
{
    #[DataProvider('metadataProvider')]
    public function testOnMetadata(Metadata $metadata): void
    {
        $this->assertSame($metadata->getDescription(), 'stringable_description');
    }

    public static function metadataProvider(): \Generator
    {
        $stringableDescription = new class implements \Stringable {
            public function __toString(): string
            {
                return 'stringable_description';
            }
        };
        $args = [
            'description' => $stringableDescription,
        ];

        yield [new Get(...$args)];
        yield [new GetCollection(...$args)];
        yield [new Post(...$args)];
        yield [new Put(...$args)];
        yield [new Patch(...$args)];
        yield [new Delete(...$args)];
        yield [new Error(...$args)];
        yield [new NotExposed(...$args)];
        yield [new Query(...$args)];
        yield [new QueryCollection(...$args)];
        yield [new Mutation(...$args)];
        yield [new Subscription(...$args)];
        yield [new ApiResource(...$args)];
        yield [new ErrorResource(...$args)];
        yield [new RestfulApi(...$args)];
    }

    public function testOnApiProperty(): void
    {
        $stringableDescription = new class implements \Stringable {
            public function __toString(): string
            {
                return 'stringable_description';
            }
        };

        $metadata = new ApiProperty(
            description: $stringableDescription,
        );

        $this->assertSame($metadata->getDescription(), 'stringable_description');
    }
}
