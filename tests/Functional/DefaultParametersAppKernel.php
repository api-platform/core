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

namespace ApiPlatform\Tests\Functional;

use ApiPlatform\Metadata\HeaderParameter;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @author Maxence Castel <maxence.castel59@gmail.com>
 */
class DefaultParametersAppKernel extends \AppKernel
{
    protected function configureContainer(ContainerBuilder $c, LoaderInterface $loader): void
    {
        parent::configureContainer($c, $loader);

        $loader->load(static function (ContainerBuilder $container) {
            if ($container->hasDefinition('phpunit_resource_name_collection')) {
                $container->removeDefinition('phpunit_resource_name_collection');
            }

            $container->loadFromExtension('api_platform', [
                'defaults' => [
                    'extra_properties' => [
                        'deduplicate_resource_short_names' => true,
                    ],
                    'parameters' => [
                        HeaderParameter::class => [
                            'key' => 'API-Key',
                            'required' => false,
                            'description' => 'API key for authentication',
                            'schema' => ['type' => 'string'],
                        ],
                    ],
                ],
            ]);
        });
    }
}
