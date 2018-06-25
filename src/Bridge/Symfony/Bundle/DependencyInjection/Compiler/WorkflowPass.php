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

use ApiPlatform\Core\Bridge\Symfony\Workflow\Metadata\Resource\Factory\WorkflowOperationResourceMetadataFactory;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Extract classes that are bound to a workflow to build up their custom metadata.
 *
 * @see ApiPlatform\Workflow\Metadata\Resource\Factory\WorkflowOperationResourceMetadataFactory
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
final class WorkflowPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('workflow.registry')) {
            return;
        }

        $registry = $container->getDefinition('workflow.registry');
        $factory = $container->getDefinition(WorkflowOperationResourceMetadataFactory::class);
        $arguments = [];

        foreach ($registry->getMethodCalls() as $methodCall) {
            $supportsStrategy = $methodCall[1][1];
            $arguments[] = $supportsStrategy->getArguments()[0];
        }

        $factory->setArgument(0, $arguments);
    }
}
