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
use PHPUnit\Framework\TestCase;

/**
 * @author Aurimas Rimkus <aurimas1rimkus@gmail.com>
 */
final class StringableSecurityParameterTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('metadataProvider')]
    public function testOnMetadata(Metadata $metadata): void
    {
        $this->assertSame($metadata->getSecurity(), 'stringable_security');
        $this->assertSame($metadata->getSecurityPostDenormalize(), 'stringable_security_post_denormalize');
        $this->assertSame($metadata->getSecurityPostValidation(), 'stringable_security_post_validation');
    }

    public static function metadataProvider(): \Generator
    {
        $stringableSecurity = new class implements \Stringable {
            public function __toString(): string
            {
                return 'stringable_security';
            }
        };
        $stringableSecurityPostDenormalize = new class implements \Stringable {
            public function __toString(): string
            {
                return 'stringable_security_post_denormalize';
            }
        };
        $stringableSecurityPostValidation = new class implements \Stringable {
            public function __toString(): string
            {
                return 'stringable_security_post_validation';
            }
        };
        $args = [
            'security' => $stringableSecurity,
            'securityPostDenormalize' => $stringableSecurityPostDenormalize,
            'securityPostValidation' => $stringableSecurityPostValidation,
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
        $stringableSecurity = new class implements \Stringable {
            public function __toString(): string
            {
                return 'stringable_security';
            }
        };
        $stringableSecurityPostDenormalize = new class implements \Stringable {
            public function __toString(): string
            {
                return 'stringable_security_post_denormalize';
            }
        };

        $metadata = new ApiProperty(
            security: $stringableSecurity,
            securityPostDenormalize: $stringableSecurityPostDenormalize,
        );

        $this->assertSame($metadata->getSecurity(), 'stringable_security');
        $this->assertSame($metadata->getSecurityPostDenormalize(), 'stringable_security_post_denormalize');
    }
}
