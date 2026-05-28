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

use ApiPlatform\Symfony\Bundle\DependencyInjection\Compiler\PropertyInfoTagPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * @see https://github.com/api-platform/core/issues/8201
 */
final class PropertyInfoTagPassTest extends TestCase
{
    public function testBridgesPublicTagsToPrivateNamespace(): void
    {
        $container = new ContainerBuilder();
        $container->setDefinition('framework.reflection_extractor', (new Definition(\stdClass::class))
            ->addTag('property_info.type_extractor', ['priority' => -100])
            ->addTag('property_info.list_extractor'));

        (new PropertyInfoTagPass())->process($container);

        $definition = $container->getDefinition('framework.reflection_extractor');
        $this->assertSame([['priority' => -100]], $definition->getTag('api_platform.property_info.type_extractor'));
        $this->assertSame([[]], $definition->getTag('api_platform.property_info.list_extractor'));
    }

    public function testSkipsServicesAlreadyCarryingThePrivateTag(): void
    {
        $container = new ContainerBuilder();
        $container->setDefinition('api_platform.property_info.reflection_extractor', (new Definition(\stdClass::class))
            ->addTag('property_info.type_extractor', ['priority' => -1002])
            ->addTag('api_platform.property_info.type_extractor', ['priority' => -1002]));

        (new PropertyInfoTagPass())->process($container);

        $tags = $container->getDefinition('api_platform.property_info.reflection_extractor')->getTag('api_platform.property_info.type_extractor');
        $this->assertCount(1, $tags, 'Pass must not re-tag services that already carry the private tag.');
    }

    public function testDoesNotTagServicesWithoutPublicTags(): void
    {
        $container = new ContainerBuilder();
        $container->setDefinition('unrelated_service', new Definition(\stdClass::class));

        (new PropertyInfoTagPass())->process($container);

        $this->assertSame([], $container->getDefinition('unrelated_service')->getTags());
    }
}
