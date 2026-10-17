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

namespace ApiPlatform\Tests\Symfony\Bundle\DependencyInjection\Compiler;

use ApiPlatform\Symfony\Bundle\DependencyInjection\Compiler\ControllerApiOperationPass;
use ApiPlatform\Tests\Fixtures\ControllerApiOperation\ControllerApiOperationClassAndOutput;
use ApiPlatform\Tests\Fixtures\ControllerApiOperation\ControllerApiOperationDefinitions;
use ApiPlatform\Tests\Fixtures\ControllerApiOperation\ControllerApiOperationInputOnly;
use ApiPlatform\Tests\Fixtures\ControllerApiOperation\ControllerApiOperationOutputOnly;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\ControllerApiOperation\Checkout;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\ControllerApiOperation\CheckoutInput;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\ControllerApiOperation\CheckoutOutput;
use ApiPlatform\Tests\Fixtures\TestBundle\Controller\Common\ControllerApiOperationController;
use ApiPlatform\Tests\Fixtures\TestBundle\Controller\Common\CustomController;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;

final class ControllerApiOperationPassTest extends TestCase
{
    public function testRegistersControllerOperationsAndResourceClasses(): void
    {
        $container = $this->createContainer(ControllerApiOperationController::class, CustomController::class);

        (new ControllerApiOperationPass())->process($container);

        $this->assertSame(
            [
                CheckoutOutput::class => [ControllerApiOperationController::class.'::__invoke'],
                Checkout::class => [
                    ControllerApiOperationController::class.'::get',
                    ControllerApiOperationController::class.'::getCollection',
                    ControllerApiOperationController::class.'::patch',
                    ControllerApiOperationController::class.'::delete',
                ],
            ],
            $container->getParameter('api_platform.controller_operations'),
        );
        $this->assertSame([CheckoutOutput::class => true, Checkout::class => true], $container->getParameter('api_platform.controller_operation_resources'));
        $this->assertSame(['Existing'], $container->getParameter('api_platform.class_name_resources'));
    }

    public function testIgnoresUntaggedControllers(): void
    {
        $container = $this->createContainer();
        $container->setDefinition('untagged', new Definition(ControllerApiOperationController::class));

        (new ControllerApiOperationPass())->process($container);

        $this->assertSame([], $container->getParameter('api_platform.controller_operations'));
        $this->assertSame([], $container->getParameter('api_platform.controller_operation_resources'));
        $this->assertSame(['Existing'], $container->getParameter('api_platform.class_name_resources'));
    }

    public function testResourceClassIsRequired(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(ControllerApiOperationDefinitions::class.'::withoutResourceClass');

        (new ControllerApiOperationPass())->process($this->createContainer(ControllerApiOperationDefinitions::class));
    }

    public function testInputOnlyThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('an "input" alone does not define a resource');

        (new ControllerApiOperationPass())->process($this->createContainer(ControllerApiOperationInputOnly::class));
    }

    public function testClassTakesPrecedenceOverOutput(): void
    {
        $container = $this->createContainer(ControllerApiOperationClassAndOutput::class);

        (new ControllerApiOperationPass())->process($container);

        $this->assertSame([CheckoutInput::class => true], $container->getParameter('api_platform.controller_operation_resources'));
    }

    public function testOutputIsResourceWithoutClass(): void
    {
        $container = $this->createContainer(ControllerApiOperationOutputOnly::class);

        (new ControllerApiOperationPass())->process($container);

        $this->assertSame([CheckoutOutput::class => true], $container->getParameter('api_platform.controller_operation_resources'));
    }

    private function createContainer(string ...$controllers): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setParameter('api_platform.class_name_resources', ['Existing']);
        foreach ($controllers as $controller) {
            $container->setDefinition($controller, (new Definition($controller))->addTag('controller.service_arguments'));
        }

        return $container;
    }
}
