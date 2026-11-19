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

namespace ApiPlatform\Laravel\Tests\Eloquent\Metadata\Factory\Resource;

use ApiPlatform\Laravel\Eloquent\Metadata\Factory\Resource\EloquentResourceCollectionMetadataFactory;
use ApiPlatform\Laravel\workbench\app\Enums\BookStatus;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase;

/**
 * @see https://github.com/api-platform/core/issues/8138
 */
final class EloquentResourceCollectionMetadataFactoryTest extends TestCase
{
    use WithWorkbench;

    public function testEnumClassIsNotInstantiated(): void
    {
        $decorated = $this->createMock(ResourceMetadataCollectionFactoryInterface::class);
        $expected = new ResourceMetadataCollection(BookStatus::class);
        $decorated->expects($this->once())
            ->method('create')
            ->with(BookStatus::class)
            ->willReturn($expected);

        $factory = new EloquentResourceCollectionMetadataFactory($decorated);

        $this->assertSame($expected, $factory->create(BookStatus::class));
    }
}
