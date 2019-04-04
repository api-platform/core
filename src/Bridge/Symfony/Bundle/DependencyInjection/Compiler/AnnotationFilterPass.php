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

namespace ApiPlatform\Core\Bridge\Symfony\Bundle\DependencyInjection\Compiler;

use ApiPlatform\Core\Util\AnnotationFilterExtractorTrait;
use ApiPlatform\Core\Util\ReflectionClassRecursiveIterator;
use Doctrine\Common\Annotations\Reader;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;

/**
 * Injects filters.
 *
 * @internal
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
final class AnnotationFilterPass implements CompilerPassInterface
{
    use AnnotationFilterExtractorTrait;

    private const TAG_FILTER_NAME = 'api_platform.filter';

    /**
     * @var Reader|null
     */
    private $reader;

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $resourceClassDirectories = $container->getParameter('api_platform.resource_class_directories');

        foreach (ReflectionClassRecursiveIterator::getReflectionClassesFromDirectories($resourceClassDirectories) as $className => $reflectionClass) {
            $this->createFilterDefinitions($reflectionClass, $container);
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    private function createFilterDefinitions(\ReflectionClass $reflectionClass, ContainerBuilder $container): void
    {
        $reader = $this->reader ?? $this->reader = $container->get('annotation_reader');

        foreach ($this->readFilterAnnotations($reflectionClass, $reader) as $id => [$arguments, $filterClass]) {
            if ($container->has($id)) {
                continue;
            }

            if ($container->has($filterClass) && ($definition = $container->findDefinition($filterClass))->isAbstract()) {
                $definition = new ChildDefinition($definition->getClass());
            } elseif ($reflectionClass = $container->getReflectionClass($filterClass, false)) {
                $definition = new Definition($reflectionClass->getName());
                $definition->setAutoconfigured(true);
            } else {
                throw new InvalidArgumentException(sprintf('Class "%s" used for service "%s" cannot be found.', $filterClass, $id));
            }

            $definition->addTag(self::TAG_FILTER_NAME);
            $definition->setAutowired(true);

            foreach ($arguments as $key => $value) {
                $definition->setArgument("$$key", $value);
            }

            $container->setDefinition($id, $definition);
        }
    }
}
