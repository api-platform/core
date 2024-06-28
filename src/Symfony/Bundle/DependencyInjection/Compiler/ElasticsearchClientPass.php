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

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Creates the Elasticsearch client.
 *
 * @author Baptiste Meyer <baptiste@les-tilleuls.coop>
 */
final class ElasticsearchClientPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        if (!$container->getParameter('api_platform.elasticsearch.enabled')) {
            return;
        }

        $clientConfiguration = [];

        if ($hosts = $container->getParameter('api_platform.elasticsearch.hosts')) {
            $clientConfiguration['hosts'] = $hosts;
        }

        if (class_exists(\Elasticsearch\ClientBuilder::class)) {
            $builderName = \Elasticsearch\ClientBuilder::class;
        } else {
            $builderName = \Elastic\Elasticsearch\ClientBuilder::class;
        }

        if ($container->has('logger')) {
            $clientConfiguration['logger'] = new Reference('logger');

            // @phpstan-ignore-next-line
            if (\Elasticsearch\ClientBuilder::class === $builderName) {
                $clientConfiguration['tracer'] = new Reference('logger');
            }
        }

        $clientDefinition = $container->getDefinition('api_platform.elasticsearch.client');

        if (!$clientConfiguration) {
            $clientDefinition->setFactory([$builderName, 'build']);
        } else {
            $clientDefinition->setFactory([$builderName, 'fromConfig']);
            $clientDefinition->setArguments([$clientConfiguration]);
        }
    }
}
