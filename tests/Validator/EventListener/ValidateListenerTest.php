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

use ApiPlatform\Core\Event\EventInterface;
use ApiPlatform\Core\Event\RespondEvent;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Tests\Fixtures\DummyEntity;
use ApiPlatform\Core\Validator\EventListener\ValidateListener;
use ApiPlatform\Core\Validator\Exception\ValidationException;
use ApiPlatform\Core\Validator\ValidatorInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;

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

        $event = $this->prophesize(EventInterface::class);
        $event->getContext()->willReturn(['request' => $request])->shouldBeCalled();

        $listener = new ValidateListener($validator, $resourceMetadataFactory);
        $listener->handleEvent($event->reveal());
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
        $validationViewListener->handleEvent($event);
    }

    public function testDoNotCallWhenReceiveFlagIsFalse()
    {
        $data = new DummyEntity();
        $expectedValidationGroups = ['a', 'b', 'c'];

        $validatorProphecy = $this->prophesize(ValidatorInterface::class);
        $validatorProphecy->validate($data, ['groups' => $expectedValidationGroups])->shouldNotBeCalled();
        $validator = $validatorProphecy->reveal();

        [$resourceMetadataFactory, $event] = $this->createEventObject($expectedValidationGroups, $data, false);

        $validationViewListener = new ValidateListener($validator, $resourceMetadataFactory);
        $validationViewListener->handleEvent($event);
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
        $validationViewListener->handleEvent($event);
    }

    /**
     * @group legacy
     *
     * @expectedDeprecation The method ApiPlatform\Core\Validator\EventListener\ValidateListener::onKernelView() is deprecated since 2.5 and will be removed in 3.0.
     * @expectedDeprecation Passing an instance of "Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent" as argument of "ApiPlatform\Core\Validator\EventListener\ValidateListener::handleEvent" is deprecated since 2.5 and will not be possible anymore in 3.0. Pass an instance of "ApiPlatform\Core\Event\EventInterface" instead.
     */
    public function testLegacyOnKernelView()
    {
        $validatorProphecy = $this->prophesize(ValidatorInterface::class);
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getRequest()->willReturn(new Request());

        $listener = new ValidateListener($validatorProphecy->reveal(), $resourceMetadataFactoryProphecy->reveal());
        $listener->onKernelView($eventProphecy->reveal());
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

        $request = new Request([], [], [
            '_api_resource_class' => DummyEntity::class,
            '_api_item_operation_name' => 'create',
            '_api_format' => 'json',
            '_api_mime_type' => 'application/json',
            '_api_receive' => $receive,
        ]);

        $request->setMethod('POST');
        $event = new RespondEvent($data, ['request' => $request]);

        return [$resourceMetadataFactory, $event];
    }
}
