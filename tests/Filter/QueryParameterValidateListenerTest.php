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
use ApiPlatform\Core\Event\EventInterface;
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
    /** @var QueryParameterValidateListener */
    private $testedInstance;
    private $filterLocatorProphecy;

    /**
     * unsafe method should not use filter validations.
     */
    public function testWithUnsafeMethod()
    {
        $this->setUpWithFilters(['some_filter']);

        $request = new Request([], [], ['_api_resource_class' => Dummy::class, '_api_collection_operation_name' => 'get']);
        $request->setMethod('POST');

        $eventProphecy = $this->prophesize(EventInterface::class);
        $eventProphecy->getContext()->willReturn(['request' => $request]);

        $this->filterLocatorProphecy->has('some_filter')->shouldNotBeCalled();

        $this->testedInstance->handleEvent($eventProphecy->reveal());
    }

    /**
     * If the tested filter is non-existent, then nothing should append.
     */
    public function testWithWrongFilter()
    {
        $this->setUpWithFilters(['some_inexistent_filter']);

        $request = new Request([], [], ['_api_resource_class' => Dummy::class, '_api_collection_operation_name' => 'get']);
        $request->setMethod('GET');

        $eventProphecy = $this->prophesize(EventInterface::class);
        $eventProphecy->getContext()->willReturn(['request' => $request]);

        $this->filterLocatorProphecy->has('some_inexistent_filter')->shouldBeCalled();
        $this->filterLocatorProphecy->get('some_inexistent_filter')->shouldNotBeCalled();

        $this->testedInstance->handleEvent($eventProphecy->reveal());
    }

    /**
     * if the required parameter is not set, throw an FilterValidationException.
     */
    public function testWithRequiredFilterNotSet()
    {
        $this->setUpWithFilters(['some_filter']);

        $request = new Request([], [], ['_api_resource_class' => Dummy::class, '_api_collection_operation_name' => 'get']);
        $request->setMethod('GET');

        $eventProphecy = $this->prophesize(EventInterface::class);
        $eventProphecy->getContext()->willReturn(['request' => $request]);

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
        $this->testedInstance->handleEvent($eventProphecy->reveal());
    }

    /**
     * if the required parameter is set, no exception should be thrown.
     */
    public function testWithRequiredFilter()
    {
        $this->setUpWithFilters(['some_filter']);

        $request = new Request(
            ['required' => 'foo'],
            [],
            ['_api_resource_class' => Dummy::class, '_api_collection_operation_name' => 'get']
        );
        $request->setMethod('GET');

        $eventProphecy = $this->prophesize(EventInterface::class);
        $eventProphecy->getContext()->willReturn(['request' => $request]);

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

        $this->testedInstance->handleEvent($eventProphecy->reveal());
    }

    /**
     * @group legacy
     *
     * @expectedDeprecation The method ApiPlatform\Core\Filter\QueryParameterValidateListener::onKernelRequest() is deprecated since 2.5 and will be removed in 3.0.
     * @expectedDeprecation Passing an instance of "Symfony\Component\HttpKernel\Event\GetResponseEvent" as argument of "ApiPlatform\Core\Filter\QueryParameterValidateListener::handleEvent" is deprecated since 2.5 and will not be possible anymore in 3.0. Pass an instance of "ApiPlatform\Core\Event\EventInterface" instead.
     */
    public function testLegacyOnKernelRequest()
    {
        $this->setUpWithFilters();

        $request = new Request();
        $request->setMethod('POST');

        $eventProphecy = $this->prophesize(GetResponseEvent::class);
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();

        $this->testedInstance->onKernelRequest($eventProphecy->reveal());
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
