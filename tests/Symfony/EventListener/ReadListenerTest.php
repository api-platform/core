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

use ApiPlatform\Api\UriVariablesConverterInterface;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Serializer\SerializerContextBuilderInterface;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\Symfony\EventListener\ReadListener;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Tests\ProphecyTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class ReadListenerTest extends TestCase
{
    use ProphecyTrait;

    public function testNotAnApiPlatformRequest()
    {
        $event = $this->prophesize(RequestEvent::class);
        $event->getRequest()->willReturn(new Request())->shouldBeCalled();

        $provider = $this->prophesize(ProviderInterface::class);
        $provider->provide()->shouldNotBeCalled();
        $resourceMetadataCollectionFactory = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $serializerContextBuilder = $this->prophesize(SerializerContextBuilderInterface::class);
        $uriVariablesConverter = $this->prophesize(UriVariablesConverterInterface::class);
        $listener = new ReadListener($provider->reveal(), $resourceMetadataCollectionFactory->reveal(), $serializerContextBuilder->reveal(), $uriVariablesConverter->reveal());
        $listener->onKernelRequest($event->reveal());
    }

    public function testDoNotReadWhenReceiveFlagIsFalse()
    {
        $request = new Request([], [], ['id' => 1, 'data' => new Dummy(), '_api_resource_class' => Dummy::class, '_api_operation_name' => 'put', '_api_receive' => false]);
        $request->setMethod('PUT');

        $event = $this->prophesize(RequestEvent::class);
        $event->getRequest()->willReturn($request);

        $provider = $this->prophesize(ProviderInterface::class);
        $provider->provide()->shouldNotBeCalled();
        $resourceMetadataCollectionFactory = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataCollectionFactory->create(Dummy::class)->willReturn(new ResourceMetadataCollection(Dummy::class, [
            (new ApiResource())->withOperations(new Operations([
                'put' => new Put(),
            ])),
        ]));
        $serializerContextBuilder = $this->prophesize(SerializerContextBuilderInterface::class);
        $uriVariablesConverter = $this->prophesize(UriVariablesConverterInterface::class);
        $listener = new ReadListener($provider->reveal(), $resourceMetadataCollectionFactory->reveal(), $serializerContextBuilder->reveal(), $uriVariablesConverter->reveal());
        $listener->onKernelRequest($event->reveal());
    }

    public function testDoNotReadWhenReadIsFalse()
    {
        $request = new Request([], [], ['id' => 1, 'data' => new Dummy(), '_api_resource_class' => Dummy::class, '_api_operation_name' => 'put']);
        $request->setMethod('PUT');

        $event = $this->prophesize(RequestEvent::class);
        $event->getRequest()->willReturn($request);

        $provider = $this->prophesize(ProviderInterface::class);
        $provider->provide()->shouldNotBeCalled();
        $resourceMetadataCollectionFactory = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataCollectionFactory->create(Dummy::class)->willReturn(new ResourceMetadataCollection(Dummy::class, [
            (new ApiResource())->withOperations(new Operations([
                'put' => (new Put())->withRead(false),
            ])),
        ]));
        $serializerContextBuilder = $this->prophesize(SerializerContextBuilderInterface::class);
        $uriVariablesConverter = $this->prophesize(UriVariablesConverterInterface::class);
        $listener = new ReadListener($provider->reveal(), $resourceMetadataCollectionFactory->reveal(), $serializerContextBuilder->reveal(), $uriVariablesConverter->reveal());
        $listener->onKernelRequest($event->reveal());
    }

    public function readWithIdentifiers()
    {
        $request = new Request([], [], ['id' => '1', 'data' => new Dummy(), '_api_resource_class' => Dummy::class, '_api_operation_name' => 'get']);
        $request->setMethod('GET');

        $event = $this->prophesize(RequestEvent::class);
        $event->getRequest()->willReturn($request);

        $operation = (new Get())->withUriVariables(['id' => (new Link())->withFromClass(Dummy::class)->withIdentifiers(['id'])]);
        $provider = $this->prophesize(ProviderInterface::class);
        $provider->provide(Dummy::class, ['id' => 1], 'get', Argument::type('array'))->shouldNotBeCalled();
        $resourceMetadataCollectionFactory = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataCollectionFactory->create(Dummy::class)->willReturn(new ResourceMetadataCollection(Dummy::class, [
            (new ApiResource())->withOperations(new Operations([
                'get' => $operation,
            ])),
        ]));
        $serializerContextBuilder = $this->prophesize(SerializerContextBuilderInterface::class);
        $serializerContextBuilder->createFromRequest($request, true, Argument::type('array'))->shouldBeCalled()->willReturn(['groups' => ['a']]);
        $uriVariablesConverter = $this->prophesize(UriVariablesConverterInterface::class);
        $uriVariablesConverter->convert(['id' => '1'], Dummy::class, ['operation' => $operation])->shouldBeCalled()->willReturn(['id' => 1]);

        $listener = new ReadListener($provider->reveal(), $resourceMetadataCollectionFactory->reveal(), $serializerContextBuilder->reveal(), $uriVariablesConverter->reveal());
        $listener->onKernelRequest($event->reveal());
    }
}
