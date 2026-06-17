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

namespace ApiPlatform\Symfony\Tests\Bundle\DependencyInjection;

use ApiPlatform\Metadata\Exception\ExceptionInterface;
use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\UrlGeneratorInterface;
use ApiPlatform\Symfony\Bundle\DependencyInjection\ApiPlatformExtension;
use ApiPlatform\Tests\Fixtures\TestBundle\TestBundle;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\ORM\OptimisticLockException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\HttpFoundation\Response;

final class JsonApiUseIriAsIdDeprecationTest extends TestCase
{
    private ContainerBuilder $container;

    protected function setUp(): void
    {
        $containerParameterBag = new ParameterBag([
            'kernel.bundles' => [
                'DoctrineBundle' => DoctrineBundle::class,
                'SecurityBundle' => SecurityBundle::class,
                'TwigBundle' => TwigBundle::class,
            ],
            'kernel.bundles_metadata' => [
                'TestBundle' => [
                    'parent' => null,
                    'path' => realpath(__DIR__.'/../../../Fixtures/TestBundle'),
                    'namespace' => TestBundle::class,
                ],
            ],
            'kernel.project_dir' => __DIR__.'/../../../Fixtures/app',
            'kernel.debug' => false,
            'kernel.environment' => 'test',
        ]);

        $this->container = new ContainerBuilder($containerParameterBag);
    }

    #[Group('legacy')]
    #[IgnoreDeprecations]
    public function testNotSettingUseIriAsIdIsDeprecatedAndResolvesToTrue(): void
    {
        $this->expectUserDeprecationMessage('Since api-platform/core 4.4: Not setting "api_platform.jsonapi.use_iri_as_id" explicitly is deprecated. Its default value will change from "true" to "false" in API Platform 5.0. Set it to "true" to keep the current behavior or to "false" to use entity identifiers as the "id" field, and silence this deprecation.');

        (new ApiPlatformExtension())->load($this->buildConfig(), $this->container);

        $this->assertTrue($this->container->getDefinition('api_platform.jsonapi.normalizer.item')->getArgument(13));
        $this->assertTrue($this->container->getDefinition('api_platform.jsonapi.denormalizer.item')->getArgument(12));
    }

    public function testSettingUseIriAsIdToFalseDoesNotDeprecateAndResolvesToFalse(): void
    {
        (new ApiPlatformExtension())->load($this->buildConfig(['use_iri_as_id' => false]), $this->container);

        $this->assertFalse($this->container->getDefinition('api_platform.jsonapi.normalizer.item')->getArgument(13));
        $this->assertFalse($this->container->getDefinition('api_platform.jsonapi.denormalizer.item')->getArgument(12));
    }

    public function testSettingUseIriAsIdToTrueDoesNotDeprecateAndResolvesToTrue(): void
    {
        (new ApiPlatformExtension())->load($this->buildConfig(['use_iri_as_id' => true]), $this->container);

        $this->assertTrue($this->container->getDefinition('api_platform.jsonapi.normalizer.item')->getArgument(13));
        $this->assertTrue($this->container->getDefinition('api_platform.jsonapi.denormalizer.item')->getArgument(12));
    }

    private function buildConfig(?array $jsonapi = null): array
    {
        $config = ['api_platform' => [
            'title' => 'title',
            'description' => 'description',
            'version' => 'version',
            'enable_json_streamer' => false,
            'serializer' => ['hydra_prefix' => true],
            'formats' => [
                'json' => ['mime_types' => ['json']],
                'jsonld' => ['mime_types' => ['application/ld+json']],
                'jsonapi' => ['mime_types' => ['application/vnd.api+json']],
            ],
            'doctrine_mongodb_odm' => [
                'enabled' => true,
            ],
            'defaults' => [
                'extra_properties' => [],
                'url_generation_strategy' => UrlGeneratorInterface::ABS_URL,
            ],
            'error_formats' => [
                'jsonproblem' => ['application/problem+json'],
                'jsonld' => ['application/ld+json'],
            ],
            'patch_formats' => [],
            'exception_to_status' => [
                ExceptionInterface::class => Response::HTTP_BAD_REQUEST,
                InvalidArgumentException::class => Response::HTTP_BAD_REQUEST,
                OptimisticLockException::class => Response::HTTP_CONFLICT,
            ],
            'show_webby' => true,
            'eager_loading' => [
                'enabled' => true,
                'max_joins' => 30,
                'force_eager' => true,
                'fetch_partial' => false,
            ],
            'asset_package' => null,
            'enable_entrypoint' => true,
            'enable_docs' => true,
            'enable_swagger' => true,
            'enable_swagger_ui' => true,
            'use_symfony_listeners' => false,
        ]];

        if (null !== $jsonapi) {
            $config['api_platform']['jsonapi'] = $jsonapi;
        }

        return $config;
    }
}
