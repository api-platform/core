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

use ApiPlatform\Api\QueryParameterValidator\QueryParameterValidator;
use ApiPlatform\Exception\FilterValidationException;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Symfony\EventListener\QueryParameterValidateListener;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Tests\ProphecyTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class QueryParameterValidateListenerTest extends TestCase
{
    use ProphecyTrait;

    private $testedInstance;
    private $queryParameterValidator;

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

    public function testDoNotValidateWhenDisabledGlobally(): void
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn(new ResourceMetadataCollection(Dummy::class, [
            (new ApiResource(operations: [
                'get' => new Get(queryParameterValidationEnabled: false),
            ])),
        ]));

        $request = new Request([], [], ['_api_resource_class' => Dummy::class, '_api_operation_name' => 'get']);

        $eventProphecy = $this->prophesize(RequestEvent::class);
        $eventProphecy->getRequest()->willReturn($request);

        $queryParameterValidator = $this->prophesize(QueryParameterValidator::class);
        $queryParameterValidator->validateFilters(Argument::cetera())->shouldNotBeCalled();

        $listener = new QueryParameterValidateListener(
            $queryParameterValidator->reveal(),
            $resourceMetadataFactoryProphecy->reveal(),
        );

        $listener->onKernelRequest($eventProphecy->reveal());
    }

    public function testDoNotValidateWhenDisabledInOperationAttribute(): void
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadataCollection(Dummy::class, [
            (new ApiResource(operations: [
                'get' => new Get(queryParameterValidationEnabled: false),
            ])),
        ]));

        $request = new Request([], [], ['_api_resource_class' => Dummy::class, '_api_operation_name' => 'get']);

        $eventProphecy = $this->prophesize(RequestEvent::class);
        $eventProphecy->getRequest()->willReturn($request);

        $queryParameterValidator = $this->prophesize(QueryParameterValidator::class);
        $queryParameterValidator->validateFilters(Argument::cetera())->shouldNotBeCalled();

        $listener = new QueryParameterValidateListener(
            $queryParameterValidator->reveal(),
            $resourceMetadataFactoryProphecy->reveal(),
        );

        $listener->onKernelRequest($eventProphecy->reveal());
    }

    /**
     * If the tested filter is non-existent, then nothing should append.
     */
    public function testOnKernelRequestWithWrongFilter()
    {
        $this->setUpWithFilters(['some_inexistent_filter']);

        $request = new Request([], [], ['_api_resource_class' => Dummy::class, '_api_operation_name' => 'get']);
        $request->setMethod('GET');

        $eventProphecy = $this->prophesize(RequestEvent::class);
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();

        $this->queryParameterValidator->validateFilters(Dummy::class, ['some_inexistent_filter'], [])->shouldBeCalled();

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

        $request = new Request([], [], ['_api_resource_class' => Dummy::class, '_api_operation_name' => 'get']);
        $request->setMethod('GET');

        $eventProphecy = $this->prophesize(RequestEvent::class);
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();

        $this->queryParameterValidator
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
            ['_api_resource_class' => Dummy::class, '_api_operation_name' => 'get'],
            [],
            [],
            ['QUERY_STRING' => 'required=foo']
        );
        $request->setMethod('GET');

        $eventProphecy = $this->prophesize(RequestEvent::class);
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();

        $this->queryParameterValidator
            ->validateFilters(Dummy::class, ['some_filter'], ['required' => 'foo'])
            ->shouldBeCalled();

        $this->assertNull(
            $this->testedInstance->onKernelRequest($eventProphecy->reveal())
        );
    }

    private function setUpWithFilters(array $filters = [])
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadataCollection(Dummy::class, [
            (new ApiResource(operations: [
                'get' => new GetCollection(filters: $filters),
            ])),
        ]));

        $this->queryParameterValidator = $this->prophesize(QueryParameterValidator::class);

        $this->testedInstance = new QueryParameterValidateListener(
            $this->queryParameterValidator->reveal(),
            $resourceMetadataFactoryProphecy->reveal(),
        );
    }
}
