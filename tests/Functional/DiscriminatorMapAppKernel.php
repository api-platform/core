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

use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Enables the (opt-in) resource polymorphism through serializer discriminator maps.
 */
class DiscriminatorMapAppKernel extends \AppKernel
{
    public function getCacheDir(): string
    {
        return parent::getCacheDir().'/discriminator_map';
    }

    public function getLogDir(): string
    {
        return parent::getLogDir().'/discriminator_map';
    }

    protected function configureContainer(ContainerBuilder $c, LoaderInterface $loader): void
    {
        parent::configureContainer($c, $loader);

        $loader->load(static function (ContainerBuilder $container): void {
            $container->loadFromExtension('api_platform', ['serializer' => ['discriminator_map' => true]]);
        });
    }
}
