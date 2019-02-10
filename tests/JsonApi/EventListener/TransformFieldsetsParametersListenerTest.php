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

namespace ApiPlatform\Core\Tests\JsonApi\EventListener;

use ApiPlatform\Core\Event\EventInterface;
use ApiPlatform\Core\JsonApi\EventListener\TransformFieldsetsParametersListener;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class TransformFieldsetsParametersListenerTest extends TestCase
{
    /** @var TransformFieldsetsParametersListener */
    private $listener;

    protected function setUp()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadata('dummy'));

        $this->listener = new TransformFieldsetsParametersListener($resourceMetadataFactoryProphecy->reveal());
    }

    public function testWithInvalidFormat()
    {
        $expectedRequest = new Request();
        $expectedRequest->setRequestFormat('badformat');

        $request = $expectedRequest->duplicate();

        $eventProphecy = $this->prophesize(EventInterface::class);
        $eventProphecy->getContext()->willReturn(['request' => $request]);

        $this->listener->handleEvent($eventProphecy->reveal());

        $this->assertEquals($expectedRequest, $request);
    }

    public function testWithInvalidFilter()
    {
        $eventProphecy = $this->prophesize(EventInterface::class);

        $expectedRequest = new Request();
        $expectedRequest->setRequestFormat('jsonapi');

        $request = $expectedRequest->duplicate();
        $eventProphecy->getContext()->willReturn(['request' => $request]);
        $this->listener->handleEvent($eventProphecy->reveal());

        $this->assertEquals($expectedRequest, $request);

        $expectedRequest = $expectedRequest->duplicate(['fields' => 'foo']);

        $request = $expectedRequest->duplicate();
        $eventProphecy->getContext()->willReturn(['request' => $request]);
        $this->listener->handleEvent($eventProphecy->reveal());

        $this->assertEquals($expectedRequest, $request);
    }

    public function testWithValidFilter()
    {
        $request = new Request(
            ['fields' => ['dummy' => 'id,name,dummyFloat', 'relatedDummy' => 'id,name'], 'include' => 'relatedDummy,foo'],
            [],
            ['_api_resource_class' => Dummy::class]
        );
        $request->setRequestFormat('jsonapi');

        $eventProphecy = $this->prophesize(EventInterface::class);
        $eventProphecy->getContext()->willReturn(['request' => $request]);

        $this->listener->handleEvent($eventProphecy->reveal());

        $expectedRequest = new Request(
            ['fields' => ['dummy' => 'id,name,dummyFloat', 'relatedDummy' => 'id,name'], 'include' => 'relatedDummy,foo'],
            [],
            [
                '_api_resource_class' => Dummy::class,
                '_api_filter_property' => ['id', 'name', 'dummyFloat', 'relatedDummy' => ['id', 'name']],
                '_api_included' => ['relatedDummy'],
            ]
        );
        $expectedRequest->setRequestFormat('jsonapi');

        $this->assertEquals($expectedRequest, $request);
    }

    public function testWithIncludeWithoutFields()
    {
        $request = new Request(
            ['include' => 'relatedDummy,foo'],
            [],
            ['_api_resource_class' => Dummy::class]
        );
        $request->setRequestFormat('jsonapi');

        $eventProphecy = $this->prophesize(EventInterface::class);
        $eventProphecy->getContext()->willReturn(['request' => $request]);

        $this->listener->handleEvent($eventProphecy->reveal());

        $expectedRequest = new Request(
            ['include' => 'relatedDummy,foo'],
            [],
            [
                '_api_resource_class' => Dummy::class,
                '_api_included' => ['relatedDummy', 'foo'],
            ]
        );
        $expectedRequest->setRequestFormat('jsonapi');

        $this->assertEquals($expectedRequest, $request);
    }

    public function testWithWrongParametersTypesDoesnTAffectRequestAttributes()
    {
        $request = new Request(
            ['fields' => 'foo', 'include' => ['relatedDummy,foo']],
            [],
            ['_api_resource_class' => Dummy::class]
        );
        $request->setRequestFormat('jsonapi');

        $eventProphecy = $this->prophesize(EventInterface::class);
        $eventProphecy->getContext()->willReturn(['request' => $request]);

        $this->listener->handleEvent($eventProphecy->reveal());

        $expectedRequest = new Request(
            ['fields' => 'foo', 'include' => ['relatedDummy,foo']],
            [],
            ['_api_resource_class' => Dummy::class]
        );
        $expectedRequest->setRequestFormat('jsonapi');

        $this->assertEquals($expectedRequest, $request);
    }

    /**
     * @group legacy
     *
     * @expectedDeprecation The method ApiPlatform\Core\JsonApi\EventListener\TransformFieldsetsParametersListener::onKernelRequest() is deprecated since 2.5 and will be removed in 3.0.
     * @expectedDeprecation Passing an instance of "Symfony\Component\HttpKernel\Event\GetResponseEvent" as argument of "ApiPlatform\Core\JsonApi\EventListener\TransformFieldsetsParametersListener::handleEvent" is deprecated since 2.5 and will not be possible anymore in 3.0. Pass an instance of "ApiPlatform\Core\Event\EventInterface" instead.
     */
    public function testLegacyOnKernelRequest()
    {
        $eventProphecy = $this->prophesize(GetResponseEvent::class);
        $eventProphecy->getRequest()->willReturn(new Request())->shouldBeCalled();

        $this->listener->onKernelRequest($eventProphecy->reveal());
    }
}
