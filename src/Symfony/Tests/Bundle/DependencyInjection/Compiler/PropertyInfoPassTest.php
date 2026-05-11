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

namespace ApiPlatform\Symfony\Tests\Bundle\DependencyInjection\Compiler;

use ApiPlatform\Symfony\Bundle\DependencyInjection\Compiler\PropertyInfoPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;

class PropertyInfoPassTest extends TestCase
{
    public function testRegistersPropertyInfoFallbackWhenMissing(): void
    {
        $container = new ContainerBuilder();

        (new PropertyInfoPass())->process($container);

        $this->assertTrue($container->hasDefinition('property_info'));
        $this->assertTrue($container->hasDefinition('property_info.reflection_extractor'));

        $definition = $container->getDefinition('property_info');
        $this->assertSame(PropertyInfoExtractor::class, $definition->getClass());

        $reflectionDef = $container->getDefinition('property_info.reflection_extractor');
        $this->assertSame(ReflectionExtractor::class, $reflectionDef->getClass());
        $this->assertArrayHasKey('property_info.list_extractor', $reflectionDef->getTags());
        $this->assertArrayHasKey('property_info.type_extractor', $reflectionDef->getTags());
        $this->assertArrayHasKey('property_info.access_extractor', $reflectionDef->getTags());
        $this->assertArrayHasKey('property_info.initializable_extractor', $reflectionDef->getTags());
    }

    public function testSkipsWhenPropertyInfoDefinitionExists(): void
    {
        $container = new ContainerBuilder();
        $container->register('property_info', PropertyInfoExtractor::class);

        (new PropertyInfoPass())->process($container);

        $this->assertFalse($container->hasDefinition('property_info.reflection_extractor'));
    }

    public function testSkipsWhenPropertyInfoAliasExists(): void
    {
        $container = new ContainerBuilder();
        $container->register('some_property_info', PropertyInfoExtractor::class);
        $container->setAlias('property_info', 'some_property_info');

        (new PropertyInfoPass())->process($container);

        $this->assertFalse($container->hasDefinition('property_info.reflection_extractor'));
    }

    public function testDoesNotRegisterReflectionExtractorIfAlreadyPresent(): void
    {
        $container = new ContainerBuilder();
        $container->register('property_info.reflection_extractor', ReflectionExtractor::class);

        (new PropertyInfoPass())->process($container);

        $this->assertTrue($container->hasDefinition('property_info'));
        $existingDef = $container->getDefinition('property_info.reflection_extractor');
        $this->assertSame(ReflectionExtractor::class, $existingDef->getClass());
        $this->assertEmpty($existingDef->getTags());
    }
}
