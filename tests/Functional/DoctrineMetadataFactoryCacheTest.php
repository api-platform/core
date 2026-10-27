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

use ApiPlatform\Doctrine\Odm\Metadata\Resource\DoctrineMongoDbOdmResourceCollectionMetadataFactory;
use ApiPlatform\Doctrine\Orm\Metadata\Resource\DoctrineOrmResourceCollectionMetadataFactory;
use ApiPlatform\Metadata\Resource\Factory\CachedResourceMetadataCollectionFactory;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\SetupClassResourcesTrait;

/**
 * The Doctrine resource metadata collection factory must be decorated by the
 * metadata cache, otherwise it runs again on every ResourceMetadataCollectionFactoryInterface::create() call.
 */
final class DoctrineMetadataFactoryCacheTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [];
    }

    public function testDoctrineFactoryIsInsideTheMetadataCache(): void
    {
        self::bootKernel();

        $factory = self::getContainer()->get('api_platform.metadata.resource.metadata_collection_factory');
        $this->assertInstanceOf(CachedResourceMetadataCollectionFactory::class, $factory, 'The metadata cache must be the outermost factory.');

        $doctrineFactories = [DoctrineOrmResourceCollectionMetadataFactory::class, DoctrineMongoDbOdmResourceCollectionMetadataFactory::class];
        $decorated = [];
        while (null !== ($factory = $this->getDecorated($factory))) {
            $decorated[] = $factory::class;
        }

        $this->assertNotEmpty(array_intersect($doctrineFactories, $decorated), 'The Doctrine metadata factory must be decorated by the metadata cache.');
    }

    private function getDecorated(ResourceMetadataCollectionFactoryInterface $factory): ?ResourceMetadataCollectionFactoryInterface
    {
        foreach ((new \ReflectionObject($factory))->getProperties() as $property) {
            $value = $property->getValue($factory);
            if ($value instanceof ResourceMetadataCollectionFactoryInterface) {
                return $value;
            }
        }

        return null;
    }
}
