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

namespace ApiPlatform\Symfony\Bundle\DependencyInjection\Compiler;

use ApiPlatform\Metadata\ApiOperation;
use ApiPlatform\Metadata\HttpOperation;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;

/**
 * Discovers the controller methods carrying {@see ApiOperation} and registers their resource classes.
 *
 * @internal
 */
final class ControllerApiOperationPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $controllerOperations = [];

        foreach (array_keys($container->findTaggedServiceIds('controller.service_arguments')) as $id) {
            $definition = $container->getDefinition($id);
            if ($definition->isAbstract()) {
                continue;
            }

            $class = $container->getParameterBag()->resolveValue($definition->getClass() ?? $id);
            if (!\is_string($class) || !($reflectionClass = $container->getReflectionClass($class, false))) {
                continue;
            }

            foreach ($reflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                foreach ($method->getAttributes(ApiOperation::class) as $attribute) {
                    $controller = $reflectionClass->getName().'::'.$method->getName();
                    $resourceClass = $this->getResourceClass($attribute->newInstance()->operation);

                    if (null === $resourceClass) {
                        throw new InvalidArgumentException(\sprintf('The "#[%s]" on "%s" must define a "class", an "output" or an "input" to resolve its resource class.', ApiOperation::class, $controller));
                    }

                    $controllerOperations[$resourceClass][] = $controller;
                }
            }
        }

        $container->setParameter('api_platform.controller_operations', $controllerOperations);

        if ($controllerOperations) {
            $container->setParameter('api_platform.class_name_resources', array_values(array_unique([
                ...$container->getParameter('api_platform.class_name_resources'),
                ...array_keys($controllerOperations),
            ])));
        }
    }

    private function getResourceClass(HttpOperation $operation): ?string
    {
        foreach ([$operation->getClass(), $operation->getOutput(), $operation->getInput()] as $candidate) {
            if (\is_array($candidate)) {
                $candidate = $candidate['class'] ?? null;
            }

            if (\is_string($candidate) && '' !== $candidate) {
                return $candidate;
            }
        }

        return null;
    }
}
