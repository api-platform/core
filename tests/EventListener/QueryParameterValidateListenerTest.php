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

namespace ApiPlatform\Core\Tests\EventListener;

use ApiPlatform\Core\EventListener\QueryParameterValidateListener;
use ApiPlatform\Core\Exception\FilterValidationException;
use ApiPlatform\Core\Filter\QueryParameterValidator;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Core\Tests\ProphecyTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class QueryParameterValidateListenerTest extends TestCase
{
    use ProphecyTrait;

    private $testedInstance;
    private $queryParameterValidor;

    /**
     * unsafe method should not use filter validations.
     */
    public function testOnKernelRequestWithUnsafeMethod()
    {
        $this->setUpWithFilters();

        $request = new Request();
        $request->setMethod('POST');

        $eventProphecy = $this->prophesize(RequestEvent::class);
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();

        $this->assertNull(
            $this->testedInstance->onKernelRequest($eventProphecy->reveal())
        );
    }

    /**
     * If the tested filter is non-existent, then nothing should append.
     */
    public function testOnKernelRequestWithWrongFilter()
    {
        $this->setUpWithFilters(['some_inexistent_filter']);

        $request = new Request([], [], ['_api_resource_class' => Dummy::class, '_api_collection_operation_name' => 'get']);
        $request->setMethod('GET');

        $eventProphecy = $this->prophesize(RequestEvent::class);
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();

        $this->queryParameterValidor->validateFilters(Dummy::class, ['some_inexistent_filter'], [])->shouldBeCalled();

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

        $eventProphecy = $this->prophesize(RequestEvent::class);
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();

        $this->queryParameterValidor
            ->validateFilters(Dummy::class, ['some_filter'], [])
            ->shouldBeCalled()
            ->willThrow(new FilterValidationException(['Query parameter "required" is required']));
        $this->expectException(FilterValidationException::class);
        $this->expectExceptionMessage('Query parameter "required" is required');
        $this->testedInstance->onKernelRequest($eventProphecy->reveal());
    }

    /**
     * if the required parameter is set, no exception should be thrown.
     */
    public function testOnKernelRequestWithRequiredFilter()
    {
        $this->setUpWithFilters(['some_filter']);

        $request = new Request(
            [],
            [],
            ['_api_resource_class' => Dummy::class, '_api_collection_operation_name' => 'get'],
            [],
            [],
            ['QUERY_STRING' => 'required=foo']
        );
        $request->setMethod('GET');

        $eventProphecy = $this->prophesize(RequestEvent::class);
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();

        $this->queryParameterValidor
            ->validateFilters(Dummy::class, ['some_filter'], ['required' => 'foo'])
            ->shouldBeCalled();

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

        $this->queryParameterValidor = $this->prophesize(QueryParameterValidator::class);

        $this->testedInstance = new QueryParameterValidateListener(
            $resourceMetadataFactoryProphecy->reveal(),
            $this->queryParameterValidor->reveal()
        );
    }
}
