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

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue5396\CompositeKeyWithDifferentType;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\CompositeItem;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\CompositeLabel;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\CompositeRelation;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class CompositeIdentifierTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [CompositeItem::class, CompositeLabel::class, CompositeRelation::class, CompositeKeyWithDifferentType::class];
    }

    protected function setUp(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped();
        }

        $this->recreateSchema([CompositeItem::class, CompositeLabel::class, CompositeRelation::class]);
        $this->seedComposite();
    }

    private function seedComposite(): void
    {
        $manager = $this->getManager();
        $item = new CompositeItem();
        $item->setField1('foobar');
        $manager->persist($item);
        $manager->flush();

        for ($i = 0; $i < 4; ++$i) {
            $label = new CompositeLabel();
            $label->setValue('foo-'.$i);
            $manager->persist($label);
            $manager->flush();

            $rel = new CompositeRelation();
            $rel->setCompositeLabel($label);
            $rel->setCompositeItem($item);
            $rel->setValue('somefoobardummy');
            $manager->persist($rel);
        }
        $manager->flush();
        $manager->clear();
    }

    public function testCollectionWithCompositeIdentifiers(): void
    {
        self::createClient()->request('GET', '/composite_items');

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json');
        $this->assertJsonEquals([
            '@context' => '/contexts/CompositeItem',
            '@id' => '/composite_items',
            '@type' => 'hydra:Collection',
            'hydra:member' => [
                [
                    '@id' => '/composite_items/1',
                    '@type' => 'CompositeItem',
                    'id' => 1,
                    'field1' => 'foobar',
                    'compositeValues' => [
                        '/composite_relations/compositeItem=1;compositeLabel=1',
                        '/composite_relations/compositeItem=1;compositeLabel=2',
                        '/composite_relations/compositeItem=1;compositeLabel=3',
                        '/composite_relations/compositeItem=1;compositeLabel=4',
                    ],
                ],
            ],
            'hydra:totalItems' => 1,
        ]);
    }

    public function testCollectionOfCompositeRelations(): void
    {
        self::createClient()->request('GET', '/composite_relations');

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json');
        $this->assertJsonContains([
            '@context' => '/contexts/CompositeRelation',
            '@id' => '/composite_relations',
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => 4,
            'hydra:view' => [
                '@id' => '/composite_relations?page=1',
                '@type' => 'hydra:PartialCollectionView',
                'hydra:first' => '/composite_relations?page=1',
                'hydra:last' => '/composite_relations?page=2',
                'hydra:next' => '/composite_relations?page=2',
            ],
        ]);
    }

    public function testGetCompositeRelationByCanonicalOrder(): void
    {
        self::createClient()->request('GET', '/composite_relations/compositeItem=1;compositeLabel=1');

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json');
        $this->assertJsonEquals([
            '@context' => '/contexts/CompositeRelation',
            '@id' => '/composite_relations/compositeItem=1;compositeLabel=1',
            '@type' => 'CompositeRelation',
            'value' => 'somefoobardummy',
            'compositeItem' => '/composite_items/1',
            'compositeLabel' => '/composite_labels/1',
        ]);
    }

    public function testGetCompositeRelationByReverseOrder(): void
    {
        self::createClient()->request('GET', '/composite_relations/compositeLabel=1;compositeItem=1');

        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonContains([
            '@id' => '/composite_relations/compositeItem=1;compositeLabel=1',
            '@type' => 'CompositeRelation',
        ]);
    }

    public function testMissingCompositeIdentifierReturns404(): void
    {
        self::createClient()->request('GET', '/composite_relations/compositeLabel=1;');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testGetCompositeItem(): void
    {
        self::createClient()->request('GET', '/composite_items/1');

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json');
    }

    public function testCompositeIdentifierWithDifferentTypes(): void
    {
        self::createClient()->request('GET', '/composite_key_with_different_types/id=82133;verificationKey=7d75af772e637e45c36d041696e1128d');

        $this->assertResponseStatusCodeSame(200);
    }
}
