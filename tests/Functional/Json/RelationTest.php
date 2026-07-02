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

namespace ApiPlatform\Tests\Functional\Json;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelatedDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelationEmbedder;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\ThirdLevel;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

/**
 * Validates that JSON requests on resources accepting application/ld+json
 * responses cover embedded creation, IRI relations and plain identifiers.
 */
final class RelationTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = true;

    public static function getResources(): array
    {
        return [
            ThirdLevel::class,
            RelationEmbedder::class,
            RelatedDummy::class,
            Dummy::class,
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        if ($this->isMongoDB()) {
            $this->markTestSkipped('Not tested with MongoDB.');
        }

        $this->recreateSchema($this->getResources());
    }

    public function testCreateThirdLevelReturnsLdJson(): void
    {
        self::createClient()->request('POST', '/third_levels', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => ['level' => 3],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json');
        $this->assertJsonContains([
            '@context' => '/contexts/ThirdLevel',
            '@id' => '/third_levels/1',
            '@type' => 'ThirdLevel',
            'fourthLevel' => null,
            'badFourthLevel' => null,
            'id' => 1,
            'level' => 3,
            'test' => true,
            'relatedDummies' => [],
        ]);
    }

    public function testCreateEmbeddedRelation(): void
    {
        self::createClient()->request('POST', '/relation_embedders', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => ['anotherRelated' => ['symfony' => 'laravel']],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json');
        $this->assertJsonContains([
            '@context' => '/contexts/RelationEmbedder',
            '@id' => '/relation_embedders/1',
            '@type' => 'RelationEmbedder',
            'krondstadt' => 'Krondstadt',
            'anotherRelated' => [
                '@id' => '/related_dummies/1',
                '@type' => 'https://schema.org/Product',
                'symfony' => 'laravel',
                'thirdLevel' => null,
            ],
            'related' => null,
        ]);
    }

    public function testReplaceEmbeddedRelationCreatesNewRelated(): void
    {
        // Bootstrap a RelationEmbedder with a related dummy.
        self::createClient()->request('POST', '/relation_embedders', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => ['anotherRelated' => ['symfony' => 'laravel']],
        ]);

        self::createClient()->request('PUT', '/relation_embedders/1', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => ['anotherRelated' => ['symfony' => 'laravel2']],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json');
        $this->assertJsonContains([
            '@id' => '/relation_embedders/1',
            'anotherRelated' => [
                '@id' => '/related_dummies/2',
                '@type' => 'https://schema.org/Product',
                'symfony' => 'laravel2',
                'thirdLevel' => null,
            ],
            'related' => null,
        ]);
    }

    public function testUpdateEmbeddedRelationUsingIri(): void
    {
        self::createClient()->request('POST', '/relation_embedders', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => ['anotherRelated' => ['symfony' => 'laravel']],
        ]);

        self::createClient()->request('PUT', '/relation_embedders/1', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => ['anotherRelated' => ['id' => '/related_dummies/1', 'symfony' => 'API Platform']],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonContains([
            '@id' => '/relation_embedders/1',
            'anotherRelated' => [
                '@id' => '/related_dummies/1',
                '@type' => 'https://schema.org/Product',
                'symfony' => 'API Platform',
                'thirdLevel' => null,
            ],
            'related' => null,
        ]);
    }

    public function testUpdateEmbeddedRelationUsingPlainIdentifier(): void
    {
        self::createClient()->request('POST', '/relation_embedders', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => ['anotherRelated' => ['symfony' => 'laravel']],
        ]);

        self::createClient()->request('PUT', '/relation_embedders/1', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => ['anotherRelated' => ['id' => 1, 'symfony' => 'API Platform 2']],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonContains([
            '@id' => '/relation_embedders/1',
            'anotherRelated' => [
                '@id' => '/related_dummies/1',
                '@type' => 'https://schema.org/Product',
                'symfony' => 'API Platform 2',
                'thirdLevel' => null,
            ],
            'related' => null,
        ]);
    }

    public function testCreateRelatedDummyWithPlainIdentifierForRelation(): void
    {
        // Creates a ThirdLevel; PurgeHttpCacheListener::postFlush caches the GetCollection
        // operation under the '' + ThirdLevel + '_c' slot of IriConverter::$localOperationCache.
        self::createClient()->request('POST', '/third_levels', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => ['level' => 3],
        ]);

        // RelatedDummyPlainIdentifierDenormalizer calls getIriFromResource(ThirdLevel::class, new Get(), …).
        // Without the fix the '_c' slot collision returns the GetCollection op, producing
        // "/third_levels?id=1" instead of "/third_levels/1".
        self::createClient()->request('POST', '/related_dummies', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => ['thirdLevel' => '1'],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains([
            '@context' => '/contexts/RelatedDummy',
            '@id' => '/related_dummies/1',
            '@type' => 'https://schema.org/Product',
            'thirdLevel' => [
                '@id' => '/third_levels/1',
                '@type' => 'ThirdLevel',
                'fourthLevel' => null,
            ],
        ]);
    }

    public function testCreateDummyWithPlainIdentifiersForRelations(): void
    {
        self::createClient()->request('POST', '/related_dummies', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => new \stdClass(),
        ]);

        self::createClient()->request('POST', '/dummies', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => [
                'relatedDummy' => '1',
                'relatedDummies' => ['1'],
                'name' => 'Dummy with plain relations',
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains([
            '@context' => '/contexts/Dummy',
            '@id' => '/dummies/1',
            '@type' => 'Dummy',
            'relatedDummy' => '/related_dummies/1',
            'relatedDummies' => ['/related_dummies/1'],
            'name' => 'Dummy with plain relations',
        ]);
    }
}
