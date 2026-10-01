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

namespace ApiPlatform\Tests\Symfony\Bundle\DependencyInjection\Compiler;

use ApiPlatform\Doctrine\Common\Serializer\Mapping\Loader\DoctrineDiscriminatorMappingLoader;
use ApiPlatform\Serializer\Mapping\Loader\PropertyMetadataLoader;
use ApiPlatform\Symfony\Bundle\DependencyInjection\Compiler\SerializerMappingLoaderPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\Serializer\Mapping\Loader\AttributeLoader;

final class SerializerMappingLoaderPassTest extends TestCase
{
    public function testDoctrineDiscriminatorMappingLoadersAreAppendedAfterTheOtherLoaders(): void
    {
        $container = $this->createContainer();
        $orm = $container->register('api_platform.doctrine.orm.serializer.discriminator_mapping_loader', DoctrineDiscriminatorMappingLoader::class);
        $odm = $container->register('api_platform.doctrine_mongodb.odm.serializer.discriminator_mapping_loader', DoctrineDiscriminatorMappingLoader::class);

        (new SerializerMappingLoaderPass())->process($container);

        $chainLoaders = $container->getDefinition('serializer.mapping.chain_loader')->getArgument(0);
        $this->assertCount(3, $chainLoaders);
        $this->assertSame([$orm, $odm], \array_slice($chainLoaders, 1));

        $warmerLoaders = $container->getDefinition('serializer.mapping.cache_warmer')->getArgument(0);
        $this->assertCount(4, $warmerLoaders);
        $this->assertSame(PropertyMetadataLoader::class, $warmerLoaders[1]->getClass());
        $this->assertSame([$orm, $odm], \array_slice($warmerLoaders, 2));
    }

    public function testChainLoaderIsUntouchedWithoutDoctrineDiscriminatorMappingLoader(): void
    {
        $container = $this->createContainer();

        (new SerializerMappingLoaderPass())->process($container);

        $this->assertCount(1, $container->getDefinition('serializer.mapping.chain_loader')->getArgument(0));
        $this->assertCount(2, $container->getDefinition('serializer.mapping.cache_warmer')->getArgument(0));
    }

    private function createContainer(): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $attributeLoader = new Definition(AttributeLoader::class);
        $container->register('serializer.mapping.chain_loader')->setArguments([[$attributeLoader]]);
        $container->register('serializer.mapping.cache_warmer')->setArguments([[], '%kernel.cache_dir%/serializer.php']);
        $container->register('api_platform.serializer.property_metadata_loader', PropertyMetadataLoader::class);

        return $container;
    }
}
