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

namespace ApiPlatform\Core\Tests\Sunset;

use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Sunset\EventListener\AddSunsetHeaderListener;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

class AddSunsetHeaderListenerTest extends TestCase
{
    public function testSetSunsetHeader()
    {
        $request = new Request([], [], ['_api_resource_class' => Dummy::class, '_api_item_operation_name' => 'get']);
        $request->setMethod('GET');
        $response = new Response();
        $date = new \DateTimeImmutable('tomorrow');

        $eventProphecy = $this->prophesize(FilterResponseEvent::class);
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();
        $eventProphecy->getResponse()->willReturn($response)->shouldBeCalled();
        $event = $eventProphecy->reveal();

        $resourceMetadata = new ResourceMetadata(null, null, null, null,
            null, ['sunset' => $date->format(DATE_RFC1123)]);
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn($resourceMetadata)->shouldBeCalled();

        $listener = new AddSunsetHeaderListener($resourceMetadataFactoryProphecy->reveal());
        $listener->onKernelResponse($event);
        $this->assertEquals($date,
            \DateTime::createFromFormat(DATE_RFC1123,
                $response->headers->get('Sunset')));
    }
}
