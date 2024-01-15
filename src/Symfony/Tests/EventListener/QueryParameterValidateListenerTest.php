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

namespace ApiPlatform\Symfony\Tests\EventListener;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\ParameterValidator\Exception\ValidationException;
use ApiPlatform\ParameterValidator\ParameterValidator;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\Symfony\EventListener\QueryParameterValidateListener;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class QueryParameterValidateListenerTest extends TestCase
{
    use ProphecyTrait;

    private QueryParameterValidateListener $testedInstance;
    private ObjectProphecy $queryParameterValidator;

    /**
     * @group legacy
     * unsafe method should not use filter validations.
     */
    public function testOnKernelRequestWithUnsafeMethod(): void
    {
        $this->setUpWithFilters();

        $request = new Request();
        $request->setMethod('POST');

        $eventProphecy = $this->prophesize(RequestEvent::class);
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();

        $this->queryParameterValidator->validateFilters(Argument::cetera())->shouldNotBeCalled();

        $this->testedInstance->onKernelRequest($eventProphecy->reveal());
    }

    /**
     * @group legacy
     */
    public function testDoNotValidateWhenDisabledGlobally(): void
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn(new ResourceMetadataCollection(Dummy::class, [
            new ApiResource(operations: [
                'get' => new Get(queryParameterValidationEnabled: false),
            ]),
        ]));

        $request = new Request([], [], ['_api_resource_class' => Dummy::class, '_api_operation_name' => 'get']);

        $eventProphecy = $this->prophesize(RequestEvent::class);
        $eventProphecy->getRequest()->willReturn($request);

        $queryParameterValidator = $this->prophesize(ParameterValidator::class);
        $queryParameterValidator->validateFilters(Argument::cetera())->shouldNotBeCalled();

        $listener = new QueryParameterValidateListener(
            $queryParameterValidator->reveal(),
            $resourceMetadataFactoryProphecy->reveal(),
        );

        $listener->onKernelRequest($eventProphecy->reveal());
    }

    /**
     * @group legacy
     */
    public function testDoNotValidateWhenDisabledInOperationAttribute(): void
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadataCollection(Dummy::class, [
            new ApiResource(operations: [
                'get' => new Get(queryParameterValidationEnabled: false),
            ]),
        ]));

        $request = new Request([], [], ['_api_resource_class' => Dummy::class, '_api_operation_name' => 'get']);

        $eventProphecy = $this->prophesize(RequestEvent::class);
        $eventProphecy->getRequest()->willReturn($request);

        $queryParameterValidator = $this->prophesize(ParameterValidator::class);
        $queryParameterValidator->validateFilters(Argument::cetera())->shouldNotBeCalled();

        $listener = new QueryParameterValidateListener(
            $queryParameterValidator->reveal(),
            $resourceMetadataFactoryProphecy->reveal(),
        );

        $listener->onKernelRequest($eventProphecy->reveal());
    }

    /**
     * If the tested filter is non-existent, then nothing should append.
     *
     * @group legacy
     */
    public function testOnKernelRequestWithWrongFilter(): void
    {
        $this->setUpWithFilters(['some_inexistent_filter']);

        $request = new Request([], [], ['_api_resource_class' => Dummy::class, '_api_operation_name' => 'get']);
        $request->setMethod('GET');

        $eventProphecy = $this->prophesize(RequestEvent::class);
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();

        $this->queryParameterValidator->validateFilters(Dummy::class, ['some_inexistent_filter'], [])->shouldBeCalled();

        $this->testedInstance->onKernelRequest($eventProphecy->reveal());
    }

    /**
     * if the required parameter is not set, throw an ValidationException.
     *
     * @group legacy
     */
    public function testOnKernelRequestWithRequiredFilterNotSet(): void
    {
        $this->setUpWithFilters(['some_filter']);

        $request = new Request([], [], ['_api_resource_class' => Dummy::class, '_api_operation_name' => 'get']);
        $request->setMethod('GET');

        $eventProphecy = $this->prophesize(RequestEvent::class);
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();

        $this->queryParameterValidator
            ->validateFilters(Dummy::class, ['some_filter'], [])
            ->shouldBeCalled()
            ->willThrow(new ValidationException(['Query parameter "required" is required']));
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Query parameter "required" is required');
        $this->testedInstance->onKernelRequest($eventProphecy->reveal());
    }

    /**
     * if the required parameter is set, no exception should be thrown.
     *
     * @group legacy
     */
    public function testOnKernelRequestWithRequiredFilter(): void
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

        $this->testedInstance->onKernelRequest($eventProphecy->reveal());
    }

    private function setUpWithFilters(array $filters = []): void
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadataCollection(Dummy::class, [
            new ApiResource(operations: [
                'get' => new GetCollection(filters: $filters),
            ]),
        ]));

        $this->queryParameterValidator = $this->prophesize(ParameterValidator::class);

        $this->testedInstance = new QueryParameterValidateListener(
            $this->queryParameterValidator->reveal(),
            $resourceMetadataFactoryProphecy->reveal(),
        );
    }

    public function testOnKernelRequest(): void
    {
        $request = new Request(
            [],
            [],
            ['_api_resource_class' => Dummy::class, '_api_operation' => new GetCollection()],
            [],
            [],
            ['QUERY_STRING' => 'required=foo']
        );
        $request->setMethod('GET');

        $event = $this->createMock(RequestEvent::class);
        $event->expects($this->any())
            ->method('getRequest')
            ->willReturn($request);

        $resourceMetadataFactoryProphecy = $this->createMock(ResourceMetadataCollectionFactoryInterface::class);
        $provider = $this->createMock(ProviderInterface::class);
        $provider->expects($this->once())
            ->method('provide');

        $qp = new QueryParameterValidateListener(
            $provider,
            $resourceMetadataFactoryProphecy,
        );
        $qp->onKernelRequest($event);
    }
}
