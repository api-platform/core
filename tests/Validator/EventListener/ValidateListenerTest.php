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
use Symfony\Component\HttpFoundation\Request;
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
        $validatorProphecy->validate()->shouldNotBeCalled();
        $validator = $validatorProphecy->reveal();

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create()->shouldNotBeCalled();
        $resourceMetadataFactory = $resourceMetadataFactoryProphecy->reveal();

        $request = new Request();
        $request->setMethod('POST');

        $event = $this->prophesize(GetResponseForControllerResultEvent::class);
        $event->getRequest()->willReturn($request)->shouldBeCalled();

        $listener = new ValidateListener($validator, $resourceMetadataFactory);
        $listener->onKernelView($event->reveal());
    }

    public function testValidatorIsCalled()
    {
        $data = new DummyEntity();
        $expectedValidationGroups = ['a', 'b', 'c'];

        $validatorProphecy = $this->prophesize(ValidatorInterface::class);
        $validatorProphecy->validate($data, ['groups' => $expectedValidationGroups])->shouldBeCalled();
        $validator = $validatorProphecy->reveal();

        list($resourceMetadataFactory, $event) = $this->createEventObject($expectedValidationGroups, $data);

        $validationViewListener = new ValidateListener($validator, $resourceMetadataFactory);
        $validationViewListener->onKernelView($event);
    }

    public function testDoNotCallWhenReceiveFlagIsFalse()
    {
        $data = new DummyEntity();
        $expectedValidationGroups = ['a', 'b', 'c'];

        $validatorProphecy = $this->prophesize(ValidatorInterface::class);
        $validatorProphecy->validate($data, ['groups' => $expectedValidationGroups])->shouldNotBeCalled();
        $validator = $validatorProphecy->reveal();

        list($resourceMetadataFactory, $event) = $this->createEventObject($expectedValidationGroups, $data, false);

        $validationViewListener = new ValidateListener($validator, $resourceMetadataFactory);
        $validationViewListener->onKernelView($event);
    }

    /**
     * @expectedException \ApiPlatform\Core\Validator\Exception\ValidationException
     */
    public function testThrowsValidationExceptionWithViolationsFound()
    {
        $data = new DummyEntity();
        $expectedValidationGroups = ['a', 'b', 'c'];

        $validatorProphecy = $this->prophesize(ValidatorInterface::class);
        $validatorProphecy->validate($data, ['groups' => $expectedValidationGroups])->willThrow(new ValidationException())->shouldBeCalled();
        $validator = $validatorProphecy->reveal();

        list($resourceMetadataFactory, $event) = $this->createEventObject($expectedValidationGroups, $data);

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

        $request->setMethod(Request::METHOD_POST);
        $event = new GetResponseForControllerResultEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST, $data);

        return [$resourceMetadataFactory, $event];
    }
}
