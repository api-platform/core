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
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

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

    const TAG_FILTER_NAME = 'api_platform.filter';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $resourceClassDirectories = $container->getParameter('api_platform.resource_class_directories');
        $reader = $container->get('annotation_reader');

        foreach (ReflectionClassRecursiveIterator::getReflectionClassesFromDirectories($resourceClassDirectories) as $className => $reflectionClass) {
            $this->createFilterDefinitions($reflectionClass, $reader, $container);
        }
    }

    private function createFilterDefinitions(\ReflectionClass $reflectionClass, Reader $reader, ContainerBuilder $container)
    {
        foreach ($this->readFilterAnnotations($reflectionClass, $reader) as $id => list($arguments, $filterClass)) {
            if ($container->hasDefinition($id)) {
                continue;
            }

            $definition = new Definition();
            $definition->setClass($filterClass);
            $definition->addTag(self::TAG_FILTER_NAME);
            $definition->setAutowired(true);

            foreach ($arguments as $key => $value) {
                $definition->setArgument("$$key", $value);
            }

            $container->setDefinition($id, $definition);
        }
    }
}
