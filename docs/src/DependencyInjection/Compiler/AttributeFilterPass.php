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

namespace ApiPlatform\Playground\DependencyInjection\Compiler;

use ApiPlatform\Metadata\Util\AttributeFilterExtractorTrait;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;

/**
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
final class AttributeFilterPass implements CompilerPassInterface
{
    use AttributeFilterExtractorTrait;

    private const TAG_FILTER_NAME = 'api_platform.filter';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        foreach (get_declared_classes() as $class) {
            $this->createFilterDefinitions(new \ReflectionClass($class), $container);
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    private function createFilterDefinitions(\ReflectionClass $resourceReflectionClass, ContainerBuilder $container): void
    {
        foreach ($this->readFilterAttributes($resourceReflectionClass) as $id => [$arguments, $filterClass]) {
            if ($container->has($id)) {
                continue;
            }

            if (null === $filterReflectionClass = $container->getReflectionClass($filterClass, false)) {
                throw new InvalidArgumentException(sprintf('Class "%s" used for service "%s" cannot be found.', $filterClass, $id));
            }

            if ($container->has($filterClass) && ($parentDefinition = $container->findDefinition($filterClass))->isAbstract()) {
                $definition = new ChildDefinition($parentDefinition->getClass());
            } else {
                $definition = new Definition($filterReflectionClass->getName());
                $definition->setAutoconfigured(true);
            }

            $definition->addTag(self::TAG_FILTER_NAME);
            $definition->setAutowired(true);

            $parameterNames = [];
            if (null !== $constructorReflectionMethod = $filterReflectionClass->getConstructor()) {
                foreach ($constructorReflectionMethod->getParameters() as $reflectionParameter) {
                    $parameterNames[$reflectionParameter->name] = true;
                }
            }

            foreach ($arguments as $key => $value) {
                if (!isset($parameterNames[$key])) {
                    throw new InvalidArgumentException(sprintf('Class "%s" does not have argument "$%s".', $filterClass, $key));
                }

                $definition->setArgument("$$key", $value);
            }

            $container->setDefinition($id, $definition);
        }
    }
}
