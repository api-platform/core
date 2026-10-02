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

use ApiPlatform\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\ValidateOnce;
use ApiPlatform\Tests\Fixtures\TestBundle\Serializer\Decorator\DenormalizingJsonLdItemNormalizerDecorator;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class DecoratedItemNormalizerAppKernel extends \AppKernel
{
    public function getCacheDir(): string
    {
        return parent::getCacheDir().'/decorated_item_normalizer';
    }

    public function getLogDir(): string
    {
        return parent::getLogDir().'/decorated_item_normalizer';
    }

    protected function configureContainer(ContainerBuilder $c, LoaderInterface $loader): void
    {
        parent::configureContainer($c, $loader);

        $loader->load(static function (ContainerBuilder $container): void {
            $container->register(DenormalizingJsonLdItemNormalizerDecorator::class)
                ->setAutowired(true)
                ->setAutoconfigured(true);
        });
    }
}

final class DecoratedItemNormalizerDenormalizationTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [ValidateOnce::class];
    }

    protected static function getKernelClass(): string
    {
        return DecoratedItemNormalizerAppKernel::class;
    }

    #[IgnoreDeprecations]
    public function testDecoratorOfTheItemNormalizerStillDenormalizes(): void
    {
        $this->expectUserDeprecationMessage('Since api-platform/core 4.4: Calling "denormalize()" on "ApiPlatform\JsonLd\Serializer\ItemNormalizer" is deprecated, use "ApiPlatform\JsonLd\Serializer\ItemDenormalizer" instead.');

        $response = self::createClient()->request('POST', '/validate_once', [
            'headers' => ['Content-Type' => 'application/ld+json', 'Accept' => 'application/ld+json'],
            'json' => ['name' => 'original'],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertSame('decorated', $response->toArray()['name']);
    }
}
