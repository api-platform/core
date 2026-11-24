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

namespace ApiPlatform\Tests\Symfony\EventListener;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Symfony\EventListener\ControllerApiOperationListener;
use ApiPlatform\Tests\Fixtures\ControllerApiOperation\ControllerApiOperationDefinitions;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\ControllerApiOperation\CheckoutOutput;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class ControllerApiOperationListenerTest extends TestCase
{
    private const CONTROLLER = ControllerApiOperationDefinitions::class.'::withInnerRouteName';

    public function testBindsLocalizedRouteThroughCanonicalRoute(): void
    {
        $request = new Request(attributes: ['_controller' => self::CONTROLLER, '_route' => 'declared_route.fr', '_canonical_route' => 'declared_route']);

        $this->createListener()->onKernelRequest($this->createRequestEvent($request));

        $this->assertTrue($request->attributes->get(ControllerApiOperationListener::REQUEST_ATTRIBUTE));
    }

    public function testThrowsWhenRouteNameDiffers(): void
    {
        $request = new Request(attributes: ['_controller' => self::CONTROLLER, '_route' => 'imported_route']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Route "imported_route" points to "'.self::CONTROLLER.'", whose "#[ApiOperation]" is bound to route "declared_route". Set "routeName: \'imported_route\'" on the operation.');

        $this->createListener()->onKernelRequest($this->createRequestEvent($request));
    }

    public function testThrowsWhenControllerIsUnbound(): void
    {
        $request = new Request(attributes: ['_controller' => self::CONTROLLER]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('could not be bound to its operation');

        $this->createListener()->onKernelController(new ControllerEvent($this->createStub(HttpKernelInterface::class), [new ControllerApiOperationDefinitions(), 'withInnerRouteName'], $request, HttpKernelInterface::MAIN_REQUEST));
    }

    private function createListener(): ControllerApiOperationListener
    {
        $operation = (new Post(routeName: 'declared_route', class: CheckoutOutput::class))->withController(self::CONTROLLER);
        $collection = new ResourceMetadataCollection(CheckoutOutput::class, [(new ApiResource(class: CheckoutOutput::class))->withOperations(new Operations(['declared' => $operation]))]);

        $factory = $this->createStub(ResourceMetadataCollectionFactoryInterface::class);
        $factory->method('create')->willReturn($collection);

        return new ControllerApiOperationListener($factory, [CheckoutOutput::class => [self::CONTROLLER]]);
    }

    private function createRequestEvent(Request $request): RequestEvent
    {
        return new RequestEvent($this->createStub(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST);
    }
}
