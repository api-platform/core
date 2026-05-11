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

namespace ApiPlatform\Tests\Functional\Hal;

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

    public function testPropertyUriTemplatesRenderAsLinks(): void
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

        $response = self::createClient()->request('GET', '/property_collection_iri_onlies/1', [
            'headers' => ['Accept' => 'application/hal+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();

        $this->assertSame('/property_collection_iri_onlies/1', $body['_links']['self']['href']);
        $this->assertSame('/property-collection-relations', $body['_links']['propertyCollectionIriOnlyRelation']['href']);
        $this->assertSame('/parent/1/another-collection-operations', $body['_links']['iterableIri']['href']);
        $this->assertSame('/parent/1/property-uri-template/one-to-ones/1', $body['_links']['toOneRelation']['href']);

        $embedded = $body['_embedded'];
        $this->assertCount(2, $embedded['propertyCollectionIriOnlyRelation']);
        $this->assertSame('asb1', $embedded['propertyCollectionIriOnlyRelation'][0]['name']);
        $this->assertSame('asb2', $embedded['propertyCollectionIriOnlyRelation'][1]['name']);
        $this->assertSame('xarguš', $embedded['toOneRelation']['name']);
        $this->assertSame('/parent/1/property-uri-template/one-to-ones/1', $embedded['toOneRelation']['_links']['self']['href']);
    }
}
