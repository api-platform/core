<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Tests\Bridge\Symfony\Validator\EventListener;

use Dunglas\ApiBundle\Bridge\Symfony\Validator\EventListener\ViewListener;
use Dunglas\ApiBundle\Metadata\Resource\Factory\ItemMetadataFactoryInterface;
use Dunglas\ApiBundle\Metadata\Resource\ItemMetadata;
use Dunglas\ApiBundle\Tests\Fixtures\DummyEntity;
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

        list($itemMetadataFactory, $event) = $this->createEventObject($expectedValidationGroups, $data);

        $validationViewListener = new ViewListener($itemMetadataFactory, $validator);
        $validationViewListener->onKernelView($event);
    }

    /**
     * @expectedException \Dunglas\ApiBundle\Bridge\Symfony\Validator\Exception\ValidationException
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

        list($itemMetadataFactory, $event) = $this->createEventObject($expectedValidationGroups, $data);

        $validationViewListener = new ViewListener($itemMetadataFactory, $validator);
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
        $itemMetadata = new ItemMetadata(null, null, null, [
            'create' => [
                'validation_groups' => $expectedValidationGroups,
            ],
        ]);

        $itemMetadataFactoryProphecy = $this->prophesize(ItemMetadataFactoryInterface::class);
        $itemMetadataFactoryProphecy->create(DummyEntity::class)->willReturn($itemMetadata);
        $itemMetadataFactory = $itemMetadataFactoryProphecy->reveal();

        $kernel = $this->prophesize('Symfony\Component\HttpKernel\HttpKernelInterface')->reveal();
        $request = new Request([], [], [
            '_resource_class' => DummyEntity::class,
            '_item_operation_name' => 'create',
        ]);

        $request->setMethod(Request::METHOD_POST);
        $event = new GetResponseForControllerResultEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST, $data);

        return [$itemMetadataFactory, $event];
    }
}
