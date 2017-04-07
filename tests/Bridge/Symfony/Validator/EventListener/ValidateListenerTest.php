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

namespace ApiPlatform\Core\Tests\Bridge\Symfony\Validator\EventListener;

use ApiPlatform\Core\Bridge\Symfony\Validator\EventListener\ValidateListener;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Tests\Fixtures\DummyEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @author Samuel ROZE <samuel.roze@gmail.com>
 */
class ValidateListenerTest extends \PHPUnit_Framework_TestCase
{
    public function testValidatorIsCalled()
    {
        $data = new DummyEntity();
        $expectedValidationGroups = ['a', 'b', 'c'];

        $validatorProphecy = $this->prophesize(ValidatorInterface::class);
        $validatorProphecy->validate($data, null, $expectedValidationGroups)->shouldBeCalled();
        $validator = $validatorProphecy->reveal();

        list($resourceMetadataFactory, $event) = $this->createEventObject($expectedValidationGroups, $data);

        $validationViewListener = new ValidateListener($validator, $resourceMetadataFactory);
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
        $violationsProphecy->count()->willReturn(1)->shouldBeCalled();
        $violations = $violationsProphecy->reveal();

        $validatorProphecy = $this->prophesize(ValidatorInterface::class);
        $validatorProphecy->validate($data, null, $expectedValidationGroups)->shouldBeCalled()->willReturn($violations)->shouldBeCalled();
        $validator = $validatorProphecy->reveal();

        list($resourceMetadataFactory, $event) = $this->createEventObject($expectedValidationGroups, $data);

        $validationViewListener = new ValidateListener($validator, $resourceMetadataFactory);
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
        $resourceMetadataFactoryProphecy->create(DummyEntity::class)->willReturn($resourceMetadata)->shouldBeCalled();
        $resourceMetadataFactory = $resourceMetadataFactoryProphecy->reveal();

        $kernel = $this->prophesize(HttpKernelInterface::class)->reveal();
        $request = new Request([], [], [
            '_api_resource_class' => DummyEntity::class,
            '_api_item_operation_name' => 'create',
            '_api_format' => 'json',
            '_api_mime_type' => 'application/json',
        ]);

        $request->setMethod(Request::METHOD_POST);
        $event = new GetResponseForControllerResultEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST, $data);

        return [$resourceMetadataFactory, $event];
    }
}
