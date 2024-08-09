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

use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\Util\AttributeFilterExtractorTrait;
use ApiPlatform\Metadata\Util\ReflectionClassRecursiveIterator;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;

/**
 * Registers filter services from {@see ApiFilter} attribute.
 *
 * @internal
 *
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
        $resourceClassDirectories = $container->getParameter('api_platform.resource_class_directories');

        foreach (ReflectionClassRecursiveIterator::getReflectionClassesFromDirectories($resourceClassDirectories) as $className => $reflectionClass) {
            $this->createFilterDefinitions($reflectionClass, $container);
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    private function createFilterDefinitions(\ReflectionClass $resourceReflectionClass, ContainerBuilder $container): void
    {
        foreach ($this->readFilterAttributes($resourceReflectionClass) as $id => [$arguments, $filterClass, $filterAttribute]) {
            if ($container->has($id)) {
                continue;
            }

            if (null === $filterReflectionClass = $container->getReflectionClass($filterClass, false)) {
                throw new InvalidArgumentException(\sprintf('Class "%s" used for service "%s" cannot be found.', $filterClass, $id));
            }

            if ($container->has($filterClass) && ($parentDefinition = $container->findDefinition($filterClass))->isAbstract()) {
                $definition = new ChildDefinition($parentDefinition->getClass());
            } else {
                $definition = new Definition($filterReflectionClass->getName());
                $definition->setAutoconfigured(true);
            }

            $definition->addTag(self::TAG_FILTER_NAME);
            if ($filterAttribute->alias) {
                $definition->addTag(self::TAG_FILTER_NAME, ['id' => $filterAttribute->alias]);
            }

            $definition->setAutowired(true);

            $parameterNames = [];
            if (null !== $constructorReflectionMethod = $filterReflectionClass->getConstructor()) {
                foreach ($constructorReflectionMethod->getParameters() as $reflectionParameter) {
                    $parameterNames[$reflectionParameter->name] = true;
                }
            }

            foreach ($arguments as $key => $value) {
                if (!isset($parameterNames[$key])) {
                    throw new InvalidArgumentException(\sprintf('Class "%s" does not have argument "$%s".', $filterClass, $key));
                }

                $definition->setArgument("$$key", $value);
            }

            $container->setDefinition($id, $definition);
        }
    }
}
