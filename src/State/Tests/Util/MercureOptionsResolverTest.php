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

namespace ApiPlatform\State\Tests\Util;

use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GraphQl\Subscription;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\State\Util\MercureOptionsResolver;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class MercureOptionsResolverTest extends TestCase
{
    private function resolver(): MercureOptionsResolver
    {
        return new MercureOptionsResolver(
            $this->createStub(ResourceClassResolverInterface::class),
            $this->createStub(IriConverterInterface::class),
            $this->createStub(ResourceMetadataCollectionFactoryInterface::class),
        );
    }

    #[DataProvider('settings')]
    public function testHttpAndGraphQlResolveTheSameOptions(array|bool|string|null $mercure, ?array $expected): void
    {
        $object = (object) ['enabled' => true];
        foreach ([new Get(mercure: $mercure), new Subscription(name: 'watch', mercure: $mercure)] as $operation) {
            $this->assertSame($expected, $this->resolver()->resolve($operation, $object));
        }
    }

    public static function settings(): iterable
    {
        yield 'unset' => [null, null];
        yield 'disabled' => [false, null];
        yield 'enabled' => [true, ['enable_async_update' => true]];
        yield 'empty options' => [[], ['enable_async_update' => true]];
        yield 'explicit options' => [['private' => true, 'hub' => 'private', 'enable_async_update' => false], ['private' => true, 'hub' => 'private', 'enable_async_update' => false]];
        yield 'expression enabled' => ['object.enabled', ['enable_async_update' => true]];
        yield 'expression disabled' => ['not object.enabled', null];
        yield 'expression options' => ["{'private': object.enabled, 'private_fields': ['tenant'], 'hub': 'private'}", ['private' => true, 'private_fields' => ['tenant'], 'hub' => 'private', 'enable_async_update' => true]];
    }

    #[DataProvider('invalidSettings')]
    public function testInvalidEvaluatedOptionsAreRejected(string $mercure, string $message): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);
        $this->resolver()->resolve(new Subscription(name: 'watch', mercure: $mercure), new \stdClass());
    }

    public static function invalidSettings(): iterable
    {
        yield 'invalid return type' => ['42', 'must be a boolean, an array of options'];
        yield 'unknown option' => ["{'unknown': true}", 'does not exist'];
        yield 'public partition' => ["{'private_fields': ['tenant']}", 'requires "mercure.private" to be true'];
    }
}
