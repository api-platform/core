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

namespace ApiPlatform\Core\Tests\Filter;

use ApiPlatform\Core\Api\FilterInterface;
use ApiPlatform\Core\Exception\FilterValidationException;
use ApiPlatform\Core\Filter\QueryParameterValidateListener;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class QueryParameterValidateListenerTest extends TestCase
{
    private $testedInstance;
    private $filterLocatorProphecy;

    /**
     * unsafe method should not use filter validations.
     */
    public function testOnKernelRequestWithUnsafeMethod()
    {
        $this->setUpWithFilters();

        $request = new Request();
        $request->setMethod('POST');

        $eventProphecy = $this->prophesize(GetResponseEvent::class);
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();

        $this->assertNull(
            $this->testedInstance->onKernelRequest($eventProphecy->reveal())
        );
    }

    /**
     * If the tested filter is non-existant, then nothing should append.
     */
    public function testOnKernelRequestWithWrongFilter()
    {
        $this->setUpWithFilters(['some_inexistent_filter']);

        $request = new Request([], [], ['_api_resource_class' => Dummy::class, '_api_collection_operation_name' => 'get']);
        $request->setMethod('GET');

        $eventProphecy = $this->prophesize(GetResponseEvent::class);
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();

        $this->filterLocatorProphecy->has('some_inexistent_filter')->shouldBeCalled();
        $this->filterLocatorProphecy->get('some_inexistent_filter')->shouldNotBeCalled();

        $this->assertNull(
            $this->testedInstance->onKernelRequest($eventProphecy->reveal())
        );
    }

    /**
     * if the required parameter is not set, throw an FilterValidationException.
     */
    public function testOnKernelRequestWithRequiredFilterNotSet()
    {
        $this->setUpWithFilters(['some_filter']);

        $request = new Request([], [], ['_api_resource_class' => Dummy::class, '_api_collection_operation_name' => 'get']);
        $request->setMethod('GET');

        $eventProphecy = $this->prophesize(GetResponseEvent::class);
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();

        $this->filterLocatorProphecy
            ->has('some_filter')
            ->shouldBeCalled()
            ->willReturn(true);
        $filterProphecy = $this->prophesize(FilterInterface::class);
        $filterProphecy
            ->getDescription(Dummy::class)
            ->shouldBeCalled()
            ->willReturn([
                'required' => [
                    'required' => true,
                ],
            ]);
        $this->filterLocatorProphecy
            ->get('some_filter')
            ->shouldBeCalled()
            ->willReturn($filterProphecy->reveal());

        $this->expectException(FilterValidationException::class);
        $this->expectExceptionMessage('Query parameter "required" is required');
        $this->testedInstance->onKernelRequest($eventProphecy->reveal());
    }

    /**
     * if the required parameter is set, no exception should be throwned.
     */
    public function testOnKernelRequestWithRequiredFilter()
    {
        $this->setUpWithFilters(['some_filter']);

        $request = new Request(
            ['required' => 'foo'],
            [],
            ['_api_resource_class' => Dummy::class, '_api_collection_operation_name' => 'get']
        );
        $request->setMethod('GET');

        $eventProphecy = $this->prophesize(GetResponseEvent::class);
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();

        $this->filterLocatorProphecy
            ->has('some_filter')
            ->shouldBeCalled()
            ->willReturn(true);
        $filterProphecy = $this->prophesize(FilterInterface::class);
        $filterProphecy
            ->getDescription(Dummy::class)
            ->shouldBeCalled()
            ->willReturn([
                'required' => [
                    'required' => true,
                ],
            ]);
        $this->filterLocatorProphecy
            ->get('some_filter')
            ->shouldBeCalled()
            ->willReturn($filterProphecy->reveal());

        $this->assertNull(
            $this->testedInstance->onKernelRequest($eventProphecy->reveal())
        );
    }

    private function setUpWithFilters(array $filters = [])
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy
            ->create(Dummy::class)
            ->willReturn(
                (new ResourceMetadata('dummy'))
                ->withAttributes([
                    'filters' => $filters,
                ])
            );

        $this->filterLocatorProphecy = $this->prophesize(ContainerInterface::class);

        $this->testedInstance = new QueryParameterValidateListener(
            $resourceMetadataFactoryProphecy->reveal(),
            $this->filterLocatorProphecy->reveal()
        );
    }
}
