<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Tests\EventListener;

use Dunglas\ApiBundle\Bridge\Symfony\Validator\EventListener\ViewListener;
use Dunglas\ApiBundle\Tests\Fixtures\DummyEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * @author Teoh Han Hui <teohhanhui@gmail.com>
 */
class ValidationViewListenerTest extends \PHPUnit_Framework_TestCase
{
    public function testValidationGroupsFromCallable()
    {
        $data = new DummyEntity();
        $expectedValidationGroups = ['a', 'b', 'c'];

        $validationGroupsResolverProphecy = $this->prophesize('Dunglas\ApiBundle\Tests\Mock\ValidationGroupsResolverInterface');
        $validationGroupsResolverProphecy->getValidationGroups($data)->willReturn($expectedValidationGroups)->shouldBeCalled();
        $validationGroupsResolver = $validationGroupsResolverProphecy->reveal();

        $validatorProphecy = $this->prophesize('Symfony\Component\Validator\Validator\ValidatorInterface');
        $validatorProphecy->validate($data, null, $expectedValidationGroups)->shouldBeCalled();
        $validator = $validatorProphecy->reveal();

        $resourceTypeProphecy = $this->prophesize('Dunglas\ApiBundle\Api\ResourceInterface');
        $resourceTypeProphecy->getValidationGroups()->willReturn([$validationGroupsResolver, 'getValidationGroups'])->shouldBeCalled();
        $resourceType = $resourceTypeProphecy->reveal();

        $kernel = $this->prophesize('Symfony\Component\HttpKernel\HttpKernelInterface')->reveal();
        $request = new Request([], [], [
            '_resource_type' => $resourceType,
        ]);
        $request->setMethod(Request::METHOD_POST);
        $event = new GetResponseForControllerResultEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST, $data);

        $validationViewListener = new ViewListener($validator);
        $validationViewListener->onKernelView($event);
    }
}
