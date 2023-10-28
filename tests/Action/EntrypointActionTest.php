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

namespace ApiPlatform\Tests\Action;

use ApiPlatform\Action\EntrypointAction;
use ApiPlatform\Api\Entrypoint;
use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceNameCollection;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\State\ProviderInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 */
class EntrypointActionTest extends TestCase
{
    use ProphecyTrait;

    public function testGetEntrypoint(): void
    {
        $resourceNameCollectionFactoryProphecy = $this->prophesize(ResourceNameCollectionFactoryInterface::class);
        $resourceNameCollectionFactoryProphecy->create()->willReturn(new ResourceNameCollection(['dummies']));
        $entrypoint = new EntrypointAction($resourceNameCollectionFactoryProphecy->reveal());
        $this->assertEquals(new Entrypoint(new ResourceNameCollection(['dummies'])), $entrypoint());
    }

    public function testGetEntrypointWithProviderProcessor(): void
    {
        $expected = new Entrypoint(new ResourceNameCollection(['dummies']));
        $resourceNameCollectionFactory = $this->createMock(ResourceNameCollectionFactoryInterface::class);
        $resourceNameCollectionFactory->method('create')->willReturn(new ResourceNameCollection(['dummies']));
        $provider = $this->createMock(ProviderInterface::class);
        $provider->expects($this->once())->method('provide')->willReturn($expected);
        $processor = $this->createMock(ProcessorInterface::class);
        $processor->expects($this->once())->method('process')->willReturnArgument(0);
        $entrypoint = new EntrypointAction($resourceNameCollectionFactory, $provider, $processor);
        $this->assertEquals($expected, $entrypoint());
    }
}
