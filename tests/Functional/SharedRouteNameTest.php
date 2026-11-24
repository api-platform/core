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

use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\SharedRouteAlpha;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\SharedRouteBeta;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class SharedRouteNameTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [SharedRouteAlpha::class, SharedRouteBeta::class];
    }

    public function testOperationsSharingARouteNameKeepDistinctNames(): void
    {
        $container = static::getContainer();
        $resourceMetadataCollectionFactory = $container->get('api_platform.metadata.resource.metadata_collection_factory');
        \assert($resourceMetadataCollectionFactory instanceof ResourceMetadataCollectionFactoryInterface);
        $iriConverter = $container->get('api_platform.iri_converter');
        \assert($iriConverter instanceof IriConverterInterface);

        $alpha = $resourceMetadataCollectionFactory->create(SharedRouteAlpha::class)->getOperation('shared_route_alpha');
        $beta = $resourceMetadataCollectionFactory->create(SharedRouteBeta::class)->getOperation('shared_route_beta');

        $this->assertInstanceOf(HttpOperation::class, $alpha);
        $this->assertInstanceOf(HttpOperation::class, $beta);
        $this->assertSame('shared_static_route', $alpha->getRouteName());
        $this->assertSame('shared_static_route', $beta->getRouteName());
        $this->assertSame('/shared_static_route', $iriConverter->getIriFromResource(new SharedRouteAlpha(), operation: $alpha));
        $this->assertSame('/shared_static_route', $iriConverter->getIriFromResource(new SharedRouteBeta(), operation: $beta));
    }
}
