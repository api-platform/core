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

namespace ApiPlatform\Core\Tests\Bridge\Symfony\Bundle;

use ApiPlatform\Core\Bridge\Symfony\Bundle\ApiPlatformBundle;
use ApiPlatform\Core\Bridge\Symfony\Bundle\DependencyInjection\Compiler\AnnotationFilterPass;
use ApiPlatform\Core\Bridge\Symfony\Bundle\DependencyInjection\Compiler\DataProviderPass;
use ApiPlatform\Core\Bridge\Symfony\Bundle\DependencyInjection\Compiler\ElasticsearchClientPass;
use ApiPlatform\Core\Bridge\Symfony\Bundle\DependencyInjection\Compiler\FilterPass;
use ApiPlatform\Core\Bridge\Symfony\Bundle\DependencyInjection\Compiler\MetadataAwareNameConverterPass;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ApiPlatformBundleTest extends TestCase
{
    public function testBuild()
    {
        $containerProphecy = $this->prophesize(ContainerBuilder::class);
        $containerProphecy->addCompilerPass(Argument::type(DataProviderPass::class))->shouldBeCalled();
        $containerProphecy->addCompilerPass(Argument::type(AnnotationFilterPass::class), PassConfig::TYPE_BEFORE_OPTIMIZATION, 101)->shouldBeCalled();
        $containerProphecy->addCompilerPass(Argument::type(FilterPass::class))->shouldBeCalled();
        $containerProphecy->addCompilerPass(Argument::type(ElasticsearchClientPass::class))->shouldBeCalled();
        $containerProphecy->addCompilerPass(Argument::type(MetadataAwareNameConverterPass::class))->shouldBeCalled();

        $bundle = new ApiPlatformBundle();
        $bundle->build($containerProphecy->reveal());
    }
}
