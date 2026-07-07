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

namespace ApiPlatform\Tests\Functional\Doctrine;

use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\FetchDataFalseStateOptions;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\FetchDataFalseStateOptionsEntity;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class FetchDataFalseStateOptionsResourceTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [FetchDataFalseStateOptions::class];
    }

    public function testFetchDataFalseReturnsReferenceForStateOptionsResourceAndSkipsItemExtensions(): void
    {
        $container = static::getContainer();
        if ('mongodb' === $container->getParameter('kernel.environment')) {
            $this->markTestSkipped('getReference() is ORM specific.');
        }

        $this->recreateSchema([FetchDataFalseStateOptionsEntity::class]);

        $manager = $this->getManager();
        $entity = new FetchDataFalseStateOptionsEntity();
        $entity->name = 'test';
        $manager->persist($entity);
        $manager->flush();
        $id = $entity->id;
        $manager->clear();

        /** @var ResourceMetadataCollectionFactoryInterface $factory */
        $factory = $container->get('api_platform.metadata.resource.metadata_collection_factory');
        $operation = $factory->create(FetchDataFalseStateOptions::class)->getOperation();

        /** @var ProviderInterface $provider */
        $provider = $container->get('api_platform.doctrine.orm.state.item_provider');

        // FetchDataFalseStateOptionsItemExtension excludes every row from the item query. With fetch_data=false
        // the provider must return EntityManager::getReference() and never build that query, so the extension is
        // skipped and the reference is returned. Before the DTO fix the resource-vs-entity class mismatch made
        // getReferenceIdentifiers() return null, the provider fell through to the query, the extension ran, and
        // this resolved to null.
        $reference = $provider->provide($operation, ['id' => $id], ['fetch_data' => false]);

        $this->assertInstanceOf(FetchDataFalseStateOptionsEntity::class, $reference);
        $this->assertSame($id, $reference->getId());
    }
}
