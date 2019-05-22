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

namespace ApiPlatform\Core\Tests\Validator\EventListener;

use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Tests\Fixtures\DummyEntity;
use ApiPlatform\Core\Validator\EventListener\ValidateListener;
use ApiPlatform\Core\Validator\Exception\ValidationException;
use ApiPlatform\Core\Validator\ValidatorInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * @author Samuel ROZE <samuel.roze@gmail.com>
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @group legacy
 */
class ValidateListenerTest extends TestCase
{
    public function testNotAnApiPlatformRequest()
    {
        $validatorProphecy = $this->prophesize(ValidatorInterface::class);
        $validatorProphecy->validate(Argument::cetera())->shouldNotBeCalled();

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);

        $request = new Request();
        $request->setMethod('POST');

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getControllerResult()->willReturn([]);
        $eventProphecy->getRequest()->willReturn($request);

        $listener = new ValidateListener($validatorProphecy->reveal(), $resourceMetadataFactoryProphecy->reveal());
        $listener->onKernelView($eventProphecy->reveal());
    }

    public function testValidatorIsCalled()
    {
        $data = new DummyEntity();
        $expectedValidationGroups = ['a', 'b', 'c'];

        $validatorProphecy = $this->prophesize(ValidatorInterface::class);
        $validatorProphecy->validate($data, ['groups' => $expectedValidationGroups])->shouldBeCalled();
        $validator = $validatorProphecy->reveal();

        [$resourceMetadataFactory, $event] = $this->createEventObject($expectedValidationGroups, $data);

        $validationViewListener = new ValidateListener($validator, $resourceMetadataFactory);
        $validationViewListener->onKernelView($event);
    }

    public function testDoNotValidateWhenControllerResultIsResponse()
    {
        $validatorProphecy = $this->prophesize(ValidatorInterface::class);
        $validatorProphecy->validate(Argument::cetera())->shouldNotBeCalled();

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);

        $dummy = new DummyEntity();

        $request = new Request([], [], ['data' => $dummy, '_api_resource_class' => DummyEntity::class, '_api_collection_operation_name' => 'post', '_api_receive' => false]);
        $request->setMethod('POST');

        $response = new Response();

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getControllerResult()->willReturn($response);
        $eventProphecy->getRequest()->willReturn($request);

        $validationViewListener = new ValidateListener($validatorProphecy->reveal(), $resourceMetadataFactoryProphecy->reveal());
        $validationViewListener->onKernelView($eventProphecy->reveal());
    }

    public function testDoNotValidateWhenReceiveFlagIsFalse()
    {
        $validatorProphecy = $this->prophesize(ValidatorInterface::class);
        $validatorProphecy->validate(Argument::cetera())->shouldNotBeCalled();

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);

        $dummy = new DummyEntity();

        $request = new Request([], [], ['data' => $dummy, '_api_resource_class' => DummyEntity::class, '_api_collection_operation_name' => 'post', '_api_receive' => false]);
        $request->setMethod('POST');

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getControllerResult()->willReturn($dummy);
        $eventProphecy->getRequest()->willReturn($request);

        $validationViewListener = new ValidateListener($validatorProphecy->reveal(), $resourceMetadataFactoryProphecy->reveal());
        $validationViewListener->onKernelView($eventProphecy->reveal());
    }

    public function testDoNotValidateWhenDisabledInOperationAttribute()
    {
        $validatorProphecy = $this->prophesize(ValidatorInterface::class);
        $validatorProphecy->validate(Argument::cetera())->shouldNotBeCalled();

        $resourceMetadata = new ResourceMetadata('DummyEntity', null, null, [], [
            'post' => [
                'validate' => false,
            ],
        ]);

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(DummyEntity::class)->willReturn($resourceMetadata);

        $dummy = new DummyEntity();

        $request = new Request([], [], ['data' => $dummy, '_api_resource_class' => DummyEntity::class, '_api_collection_operation_name' => 'post']);
        $request->setMethod('POST');

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getControllerResult()->willReturn($dummy);
        $eventProphecy->getRequest()->willReturn($request);

        $validationViewListener = new ValidateListener($validatorProphecy->reveal(), $resourceMetadataFactoryProphecy->reveal());
        $validationViewListener->onKernelView($eventProphecy->reveal());
    }

    public function testThrowsValidationExceptionWithViolationsFound()
    {
        $this->expectException(ValidationException::class);

        $data = new DummyEntity();
        $expectedValidationGroups = ['a', 'b', 'c'];

        $validatorProphecy = $this->prophesize(ValidatorInterface::class);
        $validatorProphecy->validate($data, ['groups' => $expectedValidationGroups])->willThrow(new ValidationException())->shouldBeCalled();
        $validator = $validatorProphecy->reveal();

        [$resourceMetadataFactory, $event] = $this->createEventObject($expectedValidationGroups, $data);

        $validationViewListener = new ValidateListener($validator, $resourceMetadataFactory);
        $validationViewListener->onKernelView($event);
    }

    private function createEventObject($expectedValidationGroups, $data, bool $receive = true): array
    {
        $resourceMetadata = new ResourceMetadata(null, null, null, [
            'create' => ['validation_groups' => $expectedValidationGroups],
        ]);

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        if ($receive) {
            $resourceMetadataFactoryProphecy->create(DummyEntity::class)->willReturn($resourceMetadata)->shouldBeCalled();
        }
        $resourceMetadataFactory = $resourceMetadataFactoryProphecy->reveal();

        $kernel = $this->prophesize(HttpKernelInterface::class)->reveal();
        $request = new Request([], [], [
            '_api_resource_class' => DummyEntity::class,
            '_api_item_operation_name' => 'create',
            '_api_format' => 'json',
            '_api_mime_type' => 'application/json',
            '_api_receive' => $receive,
        ]);

        $request->setMethod('POST');
        $event = new GetResponseForControllerResultEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST, $data);

        return [$resourceMetadataFactory, $event];
    }
}
