<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Tests\Bridge\Symfony\Validator\EventListener;

use ApiPlatform\Core\Bridge\Symfony\Validator\EventListener\ViewListener;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Tests\Fixtures\DummyEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * @author Samuel ROZE <samuel.roze@gmail.com>
 */
class ViewListenerTest extends \PHPUnit_Framework_TestCase
{
    public function testValidatorIsCalled()
    {
        $data = new DummyEntity();
        $expectedValidationGroups = ['a', 'b', 'c'];

        $validatorProphecy = $this->prophesize('Symfony\Component\Validator\Validator\ValidatorInterface');
        $validatorProphecy->validate($data, null, $expectedValidationGroups)->shouldBeCalled();
        $validator = $validatorProphecy->reveal();

        list($resourceMetadataFactory, $event) = $this->createEventObject($expectedValidationGroups, $data);

        $validationViewListener = new ViewListener($resourceMetadataFactory, $validator);
        $validationViewListener->onKernelView($event);
    }

    /**
     * @expectedException \ApiPlatform\Core\Bridge\Symfony\Validator\Exception\ValidationException
     */
    public function testThrowsValidationExceptionWithViolationsFound()
    {
        $data = new DummyEntity();
        $expectedValidationGroups = ['a', 'b', 'c'];

        $violationsProphecy = $this->prophesize(ConstraintViolationListInterface::class);
        $violationsProphecy->count()->willReturn(1);
        $violations = $violationsProphecy->reveal();

        $validatorProphecy = $this->prophesize('Symfony\Component\Validator\Validator\ValidatorInterface');
        $validatorProphecy->validate($data, null, $expectedValidationGroups)->shouldBeCalled()->willReturn($violations);
        $validator = $validatorProphecy->reveal();

        list($resourceMetadataFactory, $event) = $this->createEventObject($expectedValidationGroups, $data);

        $validationViewListener = new ViewListener($resourceMetadataFactory, $validator);
        $validationViewListener->onKernelView($event);
    }

    /**
     * @param array $expectedValidationGroups
     * @param mixed $data
     *
     * @return array
     */
    private function createEventObject($expectedValidationGroups, $data)
    {
        $resourceMetadata = new ResourceMetadata(null, null, null, [
            'create' => [
                'validation_groups' => $expectedValidationGroups,
            ],
        ]);

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(DummyEntity::class)->willReturn($resourceMetadata);
        $resourceMetadataFactory = $resourceMetadataFactoryProphecy->reveal();

        $kernel = $this->prophesize('Symfony\Component\HttpKernel\HttpKernelInterface')->reveal();
        $request = new Request([], [], [
            '_resource_class' => DummyEntity::class,
            '_item_operation_name' => 'create',
        ]);

        $request->setMethod(Request::METHOD_POST);
        $event = new GetResponseForControllerResultEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST, $data);

        return [$resourceMetadataFactory, $event];
    }
}
