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

use ApiPlatform\Symfony\Bundle\DependencyInjection\Compiler\MetadataAwareNameConverterPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Serializer\NameConverter\MetadataAwareNameConverter;

/**
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
class MetadataAwareNameConverterPassTest extends TestCase
{
    public function testConstruct(): void
    {
        $this->assertInstanceOf(CompilerPassInterface::class, new MetadataAwareNameConverterPass());
    }

    public function testProcessWithNameConverter(): void
    {
        $pass = new MetadataAwareNameConverterPass();

        $container = new ContainerBuilder();
        $container->setDefinition('serializer.mapping.class_metadata_factory', new Definition());
        $container->setAlias('api_platform.name_converter', 'app.name_converter');

        $pass->process($container);

        // Should create API Platform's own metadata-aware converter
        $this->assertTrue($container->hasDefinition('api_platform.name_converter.metadata_aware'));
        $definition = $container->getDefinition('api_platform.name_converter.metadata_aware');
        $this->assertSame(MetadataAwareNameConverter::class, $definition->getClass());

        // Should have class metadata factory as first argument
        $args = $definition->getArguments();
        $this->assertInstanceOf(Reference::class, $args[0]);
        $this->assertSame('serializer.mapping.class_metadata_factory', (string) $args[0]);

        // Should have the user's converter as fallback (second argument)
        $this->assertInstanceOf(Reference::class, $args[1]);
        $this->assertSame('app.name_converter', (string) $args[1]);

        // Should alias api_platform.name_converter to the new service
        $this->assertTrue($container->hasAlias('api_platform.name_converter'));
        $alias = $container->getAlias('api_platform.name_converter');
        $this->assertSame('api_platform.name_converter.metadata_aware', (string) $alias);
    }

    public function testProcessWithoutNameConverter(): void
    {
        $pass = new MetadataAwareNameConverterPass();

        $container = new ContainerBuilder();
        $container->setDefinition('serializer.mapping.class_metadata_factory', new Definition());

        $pass->process($container);

        // Should still create API Platform's own metadata-aware converter (without fallback)
        $this->assertTrue($container->hasDefinition('api_platform.name_converter.metadata_aware'));
        $definition = $container->getDefinition('api_platform.name_converter.metadata_aware');
        $this->assertSame(MetadataAwareNameConverter::class, $definition->getClass());

        $args = $definition->getArguments();
        $this->assertInstanceOf(Reference::class, $args[0]);
        $this->assertSame('serializer.mapping.class_metadata_factory', (string) $args[0]);
        $this->assertNull($args[1]); // No fallback converter

        // Should alias api_platform.name_converter to the new service
        $this->assertTrue($container->hasAlias('api_platform.name_converter'));
    }

    public function testProcessWithoutClassMetadataFactory(): void
    {
        $pass = new MetadataAwareNameConverterPass();

        $container = new ContainerBuilder();
        // No serializer.mapping.class_metadata_factory defined

        $pass->process($container);

        // Should not create anything if class metadata factory is missing
        $this->assertFalse($container->hasDefinition('api_platform.name_converter.metadata_aware'));
        $this->assertFalse($container->hasAlias('api_platform.name_converter'));
    }
}
