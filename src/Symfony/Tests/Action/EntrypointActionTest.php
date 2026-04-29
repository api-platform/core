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

namespace ApiPlatform\Symfony\Tests\Action;

use ApiPlatform\Documentation\Entrypoint;
use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceNameCollection;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\Symfony\Action\EntrypointAction;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 */
class EntrypointActionTest extends TestCase
{
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
        $this->assertEquals($expected, $entrypoint(Request::create('/')));
    }

    public function testInvokeCachesResourceNameCollection(): void
    {
        $resourceNameCollectionFactory = $this->createMock(ResourceNameCollectionFactoryInterface::class);
        $resourceNameCollectionFactory->expects($this->once())
            ->method('create')
            ->willReturn(new ResourceNameCollection(['Dummy']));

        $provider = $this->createMock(ProviderInterface::class);
        $processor = $this->createMock(ProcessorInterface::class);

        $action = new EntrypointAction($resourceNameCollectionFactory, $provider, $processor);

        $request = new Request();

        $provider->expects($this->exactly(2))
            ->method('provide')
            ->willReturn(new Entrypoint(new ResourceNameCollection(['Dummy'])));

        $processor->expects($this->exactly(2))
            ->method('process');

        $action($request);

        // Test that second call does not call factory again (lazy-loading/caching)
        $action($request);
    }

    /**
     * This test ensures that instances are isolated and don't leak state.
     * In Worker mode (FrankenPHP/Swoole), static properties would cause a state leak between instances.
     */
    public function testInstancesAreIsolated(): void
    {
        $processor = $this->createMock(ProcessorInterface::class);
        $provider = $this->createMock(ProviderInterface::class);

        // Instance 1: configured with ResourceA
        $factory1 = $this->createMock(ResourceNameCollectionFactoryInterface::class);
        $factory1->method('create')->willReturn(new ResourceNameCollection(['ResourceA']));
        $action1 = new EntrypointAction($factory1, $provider, $processor);

        // Instance 2: configured with ResourceB
        $factory2 = $this->createMock(ResourceNameCollectionFactoryInterface::class);
        $factory2->method('create')->willReturn(new ResourceNameCollection(['ResourceB']));
        $action2 = new EntrypointAction($factory2, $provider, $processor);

        $request = new Request();

        // 1. Trigger action 1
        $action1($request);
        // 2. Trigger action 2 (if static were used, this would overwrite action 1's state)
        $action2($request);

        // Verification of isolation:
        $this->assertEquals(
            new ResourceNameCollection(['ResourceA']),
            $action1->provide()->getResourceNameCollection(),
            'Instance 1 was polluted by Instance 2 (likely due to a static property)'
        );

        $this->assertEquals(
            new ResourceNameCollection(['ResourceB']),
            $action2->provide()->getResourceNameCollection(),
            'Instance 2 has incorrect state.'
        );
    }
}
