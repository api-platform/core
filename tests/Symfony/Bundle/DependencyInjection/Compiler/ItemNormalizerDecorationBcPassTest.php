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

use ApiPlatform\Elasticsearch\Serializer\ItemNormalizer as ElasticsearchItemNormalizer;
use ApiPlatform\JsonLd\Serializer\ItemDenormalizer as JsonLdItemDenormalizer;
use ApiPlatform\JsonLd\Serializer\ItemNormalizer as JsonLdItemNormalizer;
use ApiPlatform\Serializer\ItemDenormalizer;
use ApiPlatform\Serializer\ItemNormalizer;
use ApiPlatform\Symfony\Bundle\DependencyInjection\Compiler\ItemNormalizerDecorationBcPass;
use ApiPlatform\Tests\Fixtures\TestBundle\Serializer\Decorator\DenormalizingJsonLdItemNormalizerDecorator;
use ApiPlatform\Tests\Fixtures\TestBundle\Serializer\Decorator\NormalizeOnlyItemNormalizerDecorator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

final class ItemNormalizerDecorationBcPassTest extends TestCase
{
    public function testDenormalizingDecoratorDetachesDenormalizerFromSerializerChain(): void
    {
        $expected = 'Since api-platform/core 4.4: Service "user.item_normalizer" decorates "api_platform.jsonld.normalizer.item" and implements "Symfony\Component\Serializer\Normalizer\DenormalizerInterface": denormalization is routed through the decorated normalizer for backward compatibility. Decorate "api_platform.jsonld.denormalizer.item" instead.';

        $container = $this->createContainer();
        $container->setDefinition('user.item_normalizer', (new Definition(DenormalizingJsonLdItemNormalizerDecorator::class))
            ->setDecoratedService('api_platform.jsonld.normalizer.item'));

        $deprecations = [];
        set_error_handler(static function (int $type, string $message) use (&$deprecations): bool {
            $deprecations[] = $message;

            return true;
        }, \E_USER_DEPRECATED);

        try {
            (new ItemNormalizerDecorationBcPass())->process($container);
        } finally {
            restore_error_handler();
        }

        $this->assertSame([$expected], $deprecations);

        $this->assertFalse($container->getDefinition('api_platform.jsonld.denormalizer.item')->hasTag('serializer.normalizer'));
        $this->assertTrue($container->getDefinition('api_platform.jsonld.normalizer.item')->hasTag('serializer.normalizer'));
        $this->assertTrue($container->getDefinition('api_platform.serializer.denormalizer.item')->hasTag('serializer.normalizer'));
    }

    public function testNormalizeOnlyDecoratorKeepsDenormalizerTag(): void
    {
        $container = $this->createContainer();
        $container->setDefinition('user.item_normalizer', (new Definition(NormalizeOnlyItemNormalizerDecorator::class))
            ->setDecoratedService('api_platform.jsonld.normalizer.item'));

        (new ItemNormalizerDecorationBcPass())->process($container);

        $this->assertTrue($container->getDefinition('api_platform.jsonld.denormalizer.item')->hasTag('serializer.normalizer'));
    }

    public function testDecoratorOfTheDenormalizerKeepsDenormalizerTag(): void
    {
        $container = $this->createContainer();
        $container->setDefinition('user.item_normalizer', (new Definition(DenormalizingJsonLdItemNormalizerDecorator::class))
            ->setDecoratedService('api_platform.jsonld.normalizer.item'));
        $container->setDefinition('user.item_denormalizer', (new Definition(DenormalizingJsonLdItemNormalizerDecorator::class))
            ->setDecoratedService('api_platform.jsonld.denormalizer.item'));

        (new ItemNormalizerDecorationBcPass())->process($container);

        $this->assertTrue($container->getDefinition('api_platform.jsonld.denormalizer.item')->hasTag('serializer.normalizer'));
    }

    public function testApiPlatformDecoratorKeepsDenormalizerTag(): void
    {
        $container = $this->createContainer();
        $container->setDefinition('api_platform.elasticsearch.normalizer.item', (new Definition(ElasticsearchItemNormalizer::class))
            ->setDecoratedService('api_platform.serializer.normalizer.item'));

        (new ItemNormalizerDecorationBcPass())->process($container);

        $this->assertTrue($container->getDefinition('api_platform.serializer.denormalizer.item')->hasTag('serializer.normalizer'));
    }

    public function testApiPlatformDecoratorOfTheDenormalizerDoesNotCountAsMigrated(): void
    {
        $expected = 'Since api-platform/core 4.4: Service "user.item_normalizer" decorates "api_platform.serializer.normalizer.item" and implements "Symfony\\Component\\Serializer\\Normalizer\\DenormalizerInterface": denormalization is routed through the decorated normalizer for backward compatibility. Decorate "api_platform.serializer.denormalizer.item" instead.';

        $container = $this->createContainer();
        $container->setDefinition('user.item_normalizer', (new Definition(DenormalizingJsonLdItemNormalizerDecorator::class))
            ->setDecoratedService('api_platform.serializer.normalizer.item'));
        $container->setDefinition('api_platform.elasticsearch.denormalizer.item', (new Definition(ElasticsearchItemNormalizer::class))
            ->setDecoratedService('api_platform.serializer.denormalizer.item'));

        $deprecations = [];
        set_error_handler(static function (int $type, string $message) use (&$deprecations): bool {
            $deprecations[] = $message;

            return true;
        }, \E_USER_DEPRECATED);

        try {
            (new ItemNormalizerDecorationBcPass())->process($container);
        } finally {
            restore_error_handler();
        }

        $this->assertSame([$expected], $deprecations);

        $this->assertFalse($container->getDefinition('api_platform.serializer.denormalizer.item')->hasTag('serializer.normalizer'));
    }

    private function createContainer(): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setDefinition('api_platform.serializer.normalizer.item', (new Definition(ItemNormalizer::class))->addTag('serializer.normalizer', ['priority' => -895]));
        $container->setDefinition('api_platform.serializer.denormalizer.item', (new Definition(ItemDenormalizer::class))->addTag('serializer.normalizer', ['priority' => -894]));
        $container->setDefinition('api_platform.jsonld.normalizer.item', (new Definition(JsonLdItemNormalizer::class))->addTag('serializer.normalizer', ['priority' => -890]));
        $container->setDefinition('api_platform.jsonld.denormalizer.item', (new Definition(JsonLdItemDenormalizer::class))->addTag('serializer.normalizer', ['priority' => -889]));

        return $container;
    }
}
