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

namespace ApiPlatform\Core\Tests\Bridge\Symfony\Bundle\DependencyInjection\Compiler;

use ApiPlatform\Core\Bridge\Eloquent\Serializer\Mapping\Loader\AnnotationLoader as EloquentAnnotationLoader;
use ApiPlatform\Core\Bridge\Symfony\Bundle\DependencyInjection\Compiler\EloquentAnnotationLoaderPass;
use ApiPlatform\Core\Tests\ProphecyTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader;
use Symfony\Component\Serializer\Mapping\Loader\YamlFileLoader;

/**
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class EloquentAnnotationLoaderPassTest extends TestCase
{
    use ProphecyTrait;

    public function testProcess(): void
    {
        $serializerLoaders = [new Definition(YamlFileLoader::class), new Definition(AnnotationLoader::class)];

        $chainLoaderDefinitionProphecy = $this->prophesize(Definition::class);
        $chainLoaderDefinitionProphecy->getArgument(0)->willReturn($serializerLoaders);
        $chainLoaderDefinitionProphecy->replaceArgument(0, Argument::that(static function (array $serializerLoaders) {
            return 2 === \count($serializerLoaders) && YamlFileLoader::class === $serializerLoaders[0]->getClass() && EloquentAnnotationLoader::class === $serializerLoaders[1]->getClass();
        }))->shouldBeCalled();

        $containerBuilderProphecy = $this->prophesize(ContainerBuilder::class);
        $containerBuilderProphecy->getParameter('api_platform.eloquent.enabled')->willReturn(true);
        $containerBuilderProphecy->getDefinition('serializer.mapping.chain_loader')->willReturn($chainLoaderDefinitionProphecy);

        (new EloquentAnnotationLoaderPass())->process($containerBuilderProphecy->reveal());
    }

    public function testProcessDisabled(): void
    {
        $serializerLoaders = [new Definition(YamlFileLoader::class), new Definition(AnnotationLoader::class)];

        $chainLoaderDefinitionProphecy = $this->prophesize(Definition::class);
        $chainLoaderDefinitionProphecy->getArgument(0)->willReturn($serializerLoaders);
        $chainLoaderDefinitionProphecy->replaceArgument(0, Argument::any())->shouldNotBeCalled();

        $containerBuilderProphecy = $this->prophesize(ContainerBuilder::class);
        $containerBuilderProphecy->getParameter('api_platform.eloquent.enabled')->willReturn(false);
        $containerBuilderProphecy->getDefinition('serializer.mapping.chain_loader')->willReturn($chainLoaderDefinitionProphecy);

        (new EloquentAnnotationLoaderPass())->process($containerBuilderProphecy->reveal());
    }
}
