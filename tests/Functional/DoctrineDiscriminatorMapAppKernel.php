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
 * Enables the (opt-in) Doctrine discriminator maps of the active Doctrine integration.
 */
class DoctrineDiscriminatorMapAppKernel extends \AppKernel
{
    public function getCacheDir(): string
    {
        return parent::getCacheDir().'/doctrine_discriminator_map';
    }

    public function getLogDir(): string
    {
        return parent::getLogDir().'/doctrine_discriminator_map';
    }

    protected function configureContainer(ContainerBuilder $c, LoaderInterface $loader): void
    {
        parent::configureContainer($c, $loader);

        $integration = 'mongodb' === $this->environment ? 'doctrine_mongodb_odm' : 'doctrine';
        $loader->load(static function (ContainerBuilder $container) use ($integration): void {
            $container->loadFromExtension('api_platform', [
                $integration => ['discriminator_map' => true],
            ]);
        });
    }
}
