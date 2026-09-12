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
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyTableInheritance;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyTableInheritanceChild;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyTableInheritanceDifferentChild;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyTableInheritanceNotApiResourceChild;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyTableInheritanceRelated;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class TableInheritanceTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [
            DummyTableInheritance::class,
            DummyTableInheritanceChild::class,
            DummyTableInheritanceRelated::class,
            DummyTableInheritanceNotApiResourceChild::class,
        ];
    }

    public function testCreateChildExposesParentAndChildFields(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped();
        }

        $this->recreateSchema([
            DummyTableInheritance::class,
            DummyTableInheritanceChild::class,
            DummyTableInheritanceDifferentChild::class,
            DummyTableInheritanceRelated::class,
            DummyTableInheritanceNotApiResourceChild::class,
        ]);

        $response = self::createClient()->request('POST', '/dummy_table_inheritance_children', [
            'headers' => [
                'Accept' => 'application/hal+json',
                'Content-Type' => 'application/json',
            ],
            'json' => ['name' => 'foo', 'nickname' => 'bar'],
        ]);
        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('Content-Type', 'application/hal+json; charset=utf-8');
        $body = $response->toArray();
        $this->assertSame([
            '_links' => ['self' => ['href' => '/dummy_table_inheritance_children/1']],
            'nickname' => 'bar',
            'id' => 1,
            'name' => 'foo',
        ], $body);
    }

    public function testParentCollectionMixesChildAndParent(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped();
        }

        $this->recreateSchema([
            DummyTableInheritance::class,
            DummyTableInheritanceChild::class,
            DummyTableInheritanceDifferentChild::class,
            DummyTableInheritanceRelated::class,
            DummyTableInheritanceNotApiResourceChild::class,
        ]);

        $manager = $this->getManager();
        $child = new DummyTableInheritanceChild();
        $child->setName('foo');
        $child->setNickname('bar');
        $manager->persist($child);
        $parent = new DummyTableInheritance();
        $parent->setName('Foobarbaz inheritance');
        $manager->persist($parent);
        $manager->flush();

        $response = self::createClient()->request('GET', '/dummy_table_inheritances', [
            'headers' => ['Accept' => 'application/hal+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();

        $this->assertSame('/dummy_table_inheritances', $body['_links']['self']['href']);
        $this->assertSame(2, $body['totalItems']);
        $this->assertSame('/dummy_table_inheritance_children/1', $body['_links']['item'][0]['href']);
        $this->assertSame('/dummy_table_inheritances/2', $body['_links']['item'][1]['href']);

        $this->assertSame('bar', $body['_embedded']['item'][0]['nickname']);
        $this->assertSame('foo', $body['_embedded']['item'][0]['name']);
        $this->assertArrayNotHasKey('nickname', $body['_embedded']['item'][1]);
        $this->assertSame('Foobarbaz inheritance', $body['_embedded']['item'][1]['name']);
    }

    public function testRelatedEntityWithMixedChildren(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped();
        }

        $this->recreateSchema([
            DummyTableInheritance::class,
            DummyTableInheritanceChild::class,
            DummyTableInheritanceDifferentChild::class,
            DummyTableInheritanceRelated::class,
            DummyTableInheritanceNotApiResourceChild::class,
        ]);

        $manager = $this->getManager();
        $child = new DummyTableInheritanceChild();
        $child->setName('foo');
        $child->setNickname('bar');
        $manager->persist($child);
        $parent = new DummyTableInheritance();
        $parent->setName('Foobarbaz inheritance');
        $manager->persist($parent);
        $manager->flush();

        $response = self::createClient()->request('POST', '/dummy_table_inheritance_relateds', [
            'headers' => [
                'Accept' => 'application/hal+json',
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'children' => [
                    '/dummy_table_inheritance_children/1',
                    '/dummy_table_inheritances/2',
                ],
            ],
        ]);
        $this->assertResponseStatusCodeSame(201);
        $body = $response->toArray();
        $this->assertSame('/dummy_table_inheritance_relateds/1', $body['_links']['self']['href']);
        $this->assertSame('/dummy_table_inheritance_children/1', $body['_links']['children'][0]['href']);
        $this->assertSame('/dummy_table_inheritances/2', $body['_links']['children'][1]['href']);

        $children = $body['_embedded']['children'];
        $this->assertSame('/dummy_table_inheritance_children/1', $children[0]['_links']['self']['href']);
        $this->assertSame('bar', $children[0]['nickname']);
        $this->assertSame('foo', $children[0]['name']);
        $this->assertSame('/dummy_table_inheritances/2', $children[1]['_links']['self']['href']);
        $this->assertSame('Foobarbaz inheritance', $children[1]['name']);
        $this->assertArrayNotHasKey('nickname', $children[1]);
    }
}
