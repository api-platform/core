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

namespace ApiPlatform\Tests\State\Provider;

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Provider\BackedEnumProvider;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\BackedEnumIntegerResource;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\BackedEnumStringResource;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

final class BackedEnumProviderTest extends TestCase
{
    use ProphecyTrait;

    public static function provideCollection(): iterable
    {
        yield 'Integer case enum' => [BackedEnumIntegerResource::class, BackedEnumIntegerResource::cases()];
        yield 'String case enum' => [BackedEnumStringResource::class, BackedEnumStringResource::cases()];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideCollection')]
    public function testProvideCollection(string $class, array $expected): void
    {
        $operation = new GetCollection(class: $class);

        $this->testProvide($expected, $operation);
    }

    public static function provideItem(): iterable
    {
        yield 'Integer case enum' => [BackedEnumIntegerResource::class, 1, BackedEnumIntegerResource::Yes];
        yield 'String case enum' => [BackedEnumStringResource::class, 'yes', BackedEnumStringResource::Yes];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideItem')]
    public function testProvideItem(string $class, string|int $id, \BackedEnum $expected): void
    {
        $operation = new Get(class: $class);

        $this->testProvide($expected, $operation, ['id' => $id]);
    }

    private function testProvide($expected, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        $decorated = $this->prophesize(ProviderInterface::class);
        $decorated->provide(Argument::any())->shouldNotBeCalled();
        $provider = new BackedEnumProvider();

        $this->assertSame($expected, $provider->provide($operation, $uriVariables, $context));
    }
}
