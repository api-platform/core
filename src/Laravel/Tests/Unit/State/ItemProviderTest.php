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

namespace ApiPlatform\Laravel\Tests\Unit\State;

use ApiPlatform\Laravel\Eloquent\Extension\QueryExtensionInterface;
use ApiPlatform\Laravel\Eloquent\State\ItemProvider;
use ApiPlatform\Laravel\Eloquent\State\LinksHandlerInterface;
use ApiPlatform\Metadata\Get;
use Orchestra\Testbench\TestCase;
use Psr\Container\ContainerInterface;
use Workbench\App\Models\Book;

class ItemProviderTest extends TestCase
{
    public function testItemProviderWithQueryExtension(): void
    {
        $linksHandler = $this->createMock(LinksHandlerInterface::class);
        $handleLinksLocator = $this->createMock(ContainerInterface::class);
        $queryExtension = $this->createMock(QueryExtensionInterface::class);
        $queryExtension->expects($this->once())->method('apply')->willReturnArgument(0);

        $queryExtensions = [$queryExtension];
        $itemProvider = new ItemProvider($linksHandler, $handleLinksLocator, $queryExtensions);

        $operation = new Get(class: Book::class);
        $itemProvider->provide($operation);
    }
}
