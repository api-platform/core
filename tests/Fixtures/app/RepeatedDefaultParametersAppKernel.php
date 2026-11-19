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

use ApiPlatform\Metadata\HeaderParameter;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class RepeatedDefaultParametersAppKernel extends AppKernel
{
    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader): void
    {
        parent::configureContainer($container, $loader);

        $loader->load(static function (ContainerBuilder $container): void {
            if ($container->hasDefinition('phpunit_resource_name_collection')) {
                $container->removeDefinition('phpunit_resource_name_collection');
            }

            $container->loadFromExtension('api_platform', [
                'defaults' => [
                    'parameters' => [
                        'api_token' => [
                            'class' => HeaderParameter::class,
                            'key' => 'API-Token',
                            'required' => true,
                            'description' => 'API token',
                        ],
                        'request_id' => [
                            'class' => HeaderParameter::class,
                            'key' => 'Request-ID',
                            'required' => false,
                            'description' => 'Request correlation identifier',
                        ],
                    ],
                ],
            ]);
        });
    }
}
