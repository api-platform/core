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
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\AbstractUser;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyTableInheritance;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyTableInheritanceChild;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyTableInheritanceDifferentChild;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyTableInheritanceNotApiResourceChild;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyTableInheritanceRelated;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\ExternalUser;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\InternalUser;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Site;
use ApiPlatform\Tests\Fixtures\TestBundle\Model\ResourceInterface;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class TableInheritanceTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [
            DummyTableInheritance::class,
            DummyTableInheritanceChild::class,
            DummyTableInheritanceDifferentChild::class,
            DummyTableInheritanceRelated::class,
            ResourceInterface::class,
            Site::class,
            AbstractUser::class,
            ExternalUser::class,
        ];
    }

    protected function setUp(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('Table inheritance fixtures are ORM-only.');
        }

        $this->recreateSchema([
            DummyTableInheritance::class,
            DummyTableInheritanceChild::class,
            DummyTableInheritanceDifferentChild::class,
            DummyTableInheritanceNotApiResourceChild::class,
            DummyTableInheritanceRelated::class,
            Site::class,
            InternalUser::class,
            ExternalUser::class,
        ]);
    }

    private function createChild(string $name = 'foo', string $nickname = 'bar'): array
    {
        $response = self::createClient()->request('POST', '/dummy_table_inheritance_children', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['name' => $name, 'nickname' => $nickname],
        ]);

        return $response->toArray();
    }

    public function testCreateChildResource(): void
    {
        $data = $this->createChild();

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json; charset=utf-8');
        $this->assertSame('DummyTableInheritanceChild', $data['@type']);
        $this->assertSame('/contexts/DummyTableInheritanceChild', $data['@context']);
        $this->assertSame('/dummy_table_inheritance_children/1', $data['@id']);
        $this->assertSame('foo', $data['name']);
        $this->assertSame('bar', $data['nickname']);
    }

    public function testParentCollectionExposesChildren(): void
    {
        $this->createChild();

        $response = self::createClient()->request('GET', '/dummy_table_inheritances');

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json; charset=utf-8');
        $data = $response->toArray();
        $this->assertCount(1, $data['hydra:member']);
        $this->assertSame('DummyTableInheritanceChild', $data['hydra:member'][0]['@type']);
        $this->assertSame('/dummy_table_inheritance_children/1', $data['hydra:member'][0]['@id']);
    }

    public function testNonApiResourceChildAppearsAsParent(): void
    {
        $this->createChild();
        $manager = $this->getManager();
        $notApi = new DummyTableInheritanceNotApiResourceChild();
        $notApi->setName('Foobarbaz inheritance');
        $manager->persist($notApi);
        $manager->flush();

        $response = self::createClient()->request('GET', '/dummy_table_inheritances');

        $this->assertResponseStatusCodeSame(200);
        $data = $response->toArray();
        $this->assertCount(2, $data['hydra:member']);
        $this->assertSame('DummyTableInheritanceChild', $data['hydra:member'][0]['@type']);
        $this->assertSame('DummyTableInheritance', $data['hydra:member'][1]['@type']);
        $this->assertSame('/dummy_table_inheritances/2', $data['hydra:member'][1]['@id']);
        $this->assertSame(2, $data['hydra:totalItems']);
    }

    public function testCreateDifferentChildResource(): void
    {
        $response = self::createClient()->request('POST', '/dummy_table_inheritance_different_children', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['name' => 'foo', 'email' => 'bar@localhost'],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json; charset=utf-8');
        $data = $response->toArray();
        $this->assertSame('DummyTableInheritanceDifferentChild', $data['@type']);
        $this->assertSame('/contexts/DummyTableInheritanceDifferentChild', $data['@context']);
        $this->assertSame('foo', $data['name']);
        $this->assertSame('bar@localhost', $data['email']);
    }

    public function testRelatedEntityWithMixedInheritedChildren(): void
    {
        $child = $this->createChild();
        $different = self::createClient()->request('POST', '/dummy_table_inheritance_different_children', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['name' => 'foo', 'email' => 'bar@localhost'],
        ])->toArray();

        $response = self::createClient()->request('POST', '/dummy_table_inheritance_relateds', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['children' => [$child['@id'], $different['@id']]],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json; charset=utf-8');
        $data = $response->toArray();
        $this->assertSame('DummyTableInheritanceRelated', $data['@type']);
        $this->assertSame('/dummy_table_inheritance_relateds/1', $data['@id']);
        $this->assertCount(2, $data['children']);
        $this->assertSame('DummyTableInheritanceChild', $data['children'][0]['@type']);
        $this->assertSame('DummyTableInheritanceDifferentChild', $data['children'][1]['@type']);
    }

    public function testParentCollectionMixesChildrenTypes(): void
    {
        $this->createChild('foo', 'bar');
        $manager = $this->getManager();
        $notApi = new DummyTableInheritanceNotApiResourceChild();
        $notApi->setName('Foobarbaz inheritance');
        $manager->persist($notApi);
        $manager->flush();
        $this->createChild('foo2', 'bar2');
        self::createClient()->request('POST', '/dummy_table_inheritance_different_children', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['name' => 'foo', 'email' => 'bar@localhost'],
        ]);

        $response = self::createClient()->request('GET', '/dummy_table_inheritances?pagination=false');

        $this->assertResponseStatusCodeSame(200);
        $data = $response->toArray();
        $this->assertSame(4, $data['hydra:totalItems']);
        $types = array_column($data['hydra:member'], '@type');
        $this->assertContains('DummyTableInheritanceChild', $types);
        $this->assertContains('DummyTableInheritance', $types);
        $this->assertContains('DummyTableInheritanceDifferentChild', $types);
    }

    public function testInterfaceCollection(): void
    {
        $response = self::createClient()->request('GET', '/resource_interfaces', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json; charset=utf-8');
        $data = $response->toArray();
        $members = $data['hydra:member'];
        $this->assertCount(2, $members);
        $this->assertSame('ResourceInterface', $members[0]['@type']);
        $this->assertSame('/resource_interfaces/item1', $members[0]['@id']);
        $this->assertSame('item1', $members[0]['foo']);
        $this->assertSame('fooz', $members[0]['fooz']);
        $this->assertSame('ResourceInterface', $members[1]['@type']);
        $this->assertSame('/resource_interfaces/item2', $members[1]['@id']);
        $this->assertSame('item2', $members[1]['foo']);
        $this->assertSame('fooz', $members[1]['fooz']);
    }

    public function testInterfaceItem(): void
    {
        $response = self::createClient()->request('GET', '/resource_interfaces/some-id', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json; charset=utf-8');
        $data = $response->toArray();
        $this->assertSame('/contexts/ResourceInterface', $data['@context']);
        $this->assertSame('/resource_interfaces/single%20item', $data['@id']);
        $this->assertSame('ResourceInterface', $data['@type']);
        $this->assertSame('single item', $data['foo']);
        $this->assertSame('fooz', $data['fooz']);
    }

    public function testSitesWithInternalOwnerUseParentIri(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped();
        }

        $manager = $this->getManager();
        for ($i = 1; $i <= 3; ++$i) {
            $user = new InternalUser();
            $user->setFirstname('Internal');
            $user->setLastname('User');
            $user->setEmail('john.doe@example.com');
            $user->setInternalId('INT');
            $site = new Site();
            $site->setTitle('title');
            $site->setDescription('description');
            $site->setOwner($user);
            $manager->persist($site);
        }
        $manager->flush();

        $response = self::createClient()->request('GET', '/sites', [
            'headers' => ['Content-Type' => 'application/ld+json'],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json; charset=utf-8');
        $data = $response->toArray();
        $this->assertCount(3, $data['hydra:member']);
        foreach ($data['hydra:member'] as $i => $member) {
            $this->assertSame('Site', $member['@type']);
            $this->assertSame('/sites/'.($i + 1), $member['@id']);
            $this->assertSame('title', $member['title']);
            $this->assertSame('description', $member['description']);
            $ownerIri = \is_string($member['owner']) ? $member['owner'] : $member['owner']['@id'];
            $this->assertSame('/custom_users/'.($i + 1), $ownerIri);
        }
    }

    public function testSitesWithExternalOwnerUseCurrentResourceIri(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped();
        }

        $manager = $this->getManager();
        for ($i = 1; $i <= 3; ++$i) {
            $user = new ExternalUser();
            $user->setFirstname('External');
            $user->setLastname('User');
            $user->setEmail('john.doe@example.com');
            $user->setExternalId('EXT');
            $site = new Site();
            $site->setTitle('title');
            $site->setDescription('description');
            $site->setOwner($user);
            $manager->persist($site);
        }
        $manager->flush();

        $response = self::createClient()->request('GET', '/sites', [
            'headers' => ['Content-Type' => 'application/ld+json'],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $data = $response->toArray();
        foreach ($data['hydra:member'] as $i => $member) {
            $ownerIri = \is_string($member['owner']) ? $member['owner'] : $member['owner']['@id'];
            $this->assertSame('/external_users/'.($i + 1), $ownerIri);
        }
    }
}
