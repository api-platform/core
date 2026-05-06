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

namespace ApiPlatform\Tests\Functional\JsonLd;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\PropertyCollectionIriOnly;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\PropertyCollectionIriOnlyRelation;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\PropertyCollectionIriOnlyRelationSecondLevel;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\PropertyUriTemplateOneToOneRelation;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class PropertyCollectionIriOnlyTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [
            PropertyCollectionIriOnly::class,
            PropertyCollectionIriOnlyRelation::class,
            PropertyCollectionIriOnlyRelationSecondLevel::class,
            PropertyUriTemplateOneToOneRelation::class,
        ];
    }

    public function testPropertyUriTemplatesRenderAsIris(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped();
        }

        $this->recreateSchema([
            PropertyCollectionIriOnly::class,
            PropertyCollectionIriOnlyRelation::class,
            PropertyCollectionIriOnlyRelationSecondLevel::class,
            PropertyUriTemplateOneToOneRelation::class,
        ]);

        $manager = $this->getManager();
        $rel1 = new PropertyCollectionIriOnlyRelation();
        $rel1->name = 'asb1';
        $rel2 = new PropertyCollectionIriOnlyRelation();
        $rel2->name = 'asb2';
        $toOne = new PropertyUriTemplateOneToOneRelation();
        $toOne->name = 'xarguš';
        $parent = new PropertyCollectionIriOnly();
        $parent->addPropertyCollectionIriOnlyRelation($rel1);
        $parent->addPropertyCollectionIriOnlyRelation($rel2);
        $parent->setToOneRelation($toOne);
        $manager->persist($parent);
        $manager->persist($rel1);
        $manager->persist($rel2);
        $manager->persist($toOne);
        $manager->flush();

        $response = self::createClient()->request('GET', '/property_collection_iri_onlies', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'hydra:member' => [[
                '@id' => '/property_collection_iri_onlies/1',
                '@type' => 'PropertyCollectionIriOnly',
                'propertyCollectionIriOnlyRelation' => '/property-collection-relations',
                'iterableIri' => '/parent/1/another-collection-operations',
                'toOneRelation' => '/parent/1/property-uri-template/one-to-ones/1',
            ]],
        ]);

        $response = self::createClient()->request('GET', '/property_collection_iri_onlies/1', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => '/contexts/PropertyCollectionIriOnly',
            '@id' => '/property_collection_iri_onlies/1',
            '@type' => 'PropertyCollectionIriOnly',
            'propertyCollectionIriOnlyRelation' => '/property-collection-relations',
            'iterableIri' => '/parent/1/another-collection-operations',
            'toOneRelation' => '/parent/1/property-uri-template/one-to-ones/1',
        ]);
    }
}
