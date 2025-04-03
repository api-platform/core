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

namespace ApiPlatform\Tests\Functional\GraphQl;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\SecuredDummy as DocumentSecuredDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\SecuredDummyCollection as DocumentSecuredDummyCollection;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\SecuredDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\SecuredDummyCollection;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\SecuredDummyCollectionParent;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class SecurityTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;
    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [SecuredDummy::class, SecuredDummyCollection::class, SecuredDummyCollectionParent::class];
    }

    public function testQueryItem(): void
    {
        $resource = $this->isMongoDB() ? DocumentSecuredDummy::class : SecuredDummy::class;
        $this->recreateSchema([$resource]);
        $this->loadFixtures($resource);
        $client = self::createClient();
        $response = $client->request('POST', '/graphql', ['json' => [
            'query' => <<<QUERY
    {
      securedDummy(id: "/secured_dummies/1") {
        title
        description
      }
    }
QUERY,
        ]]);

        $d = $response->toArray();
        $this->assertEquals('Access Denied.', $d['errors'][0]['message']);
    }

    public function testCreateItemUnauthorized(): void
    {
        $resource = $this->isMongoDB() ? DocumentSecuredDummy::class : SecuredDummy::class;
        $this->recreateSchema([$resource]);
        $client = self::createClient();
        $response = $client->request('POST', '/graphql', ['json' => [
            'query' => <<<QUERY
mutation {
    createSecuredDummy(input: {owner: "me", title: "Hi", description: "Desc", adminOnlyProperty: "secret", clientMutationId: "auth"}) {
        securedDummy {
            title
            owner
        }
    }
}
QUERY,
        ]]);

        $d = $response->toArray();
        $this->assertEquals('Only admins can create a secured dummy.', $d['errors'][0]['message']);
    }

    public function testQueryItemWithNode(): void
    {
        $resource = $this->isMongoDB() ? DocumentSecuredDummy::class : SecuredDummy::class;
        $this->recreateSchema([$resource]);
        $this->loadFixtures($resource);
        $client = self::createClient();
        $response = $client->request('POST', '/graphql', ['json' => [
            'query' => <<<QUERY
    {
      node(id: "/secured_dummies/1") {
        ... on SecuredDummy {
            title
        }
      }
    }
QUERY,
        ]]);

        $d = $response->toArray();
        $this->assertEquals('Access Denied.', $d['errors'][0]['message']);
    }

    public function loadFixtures(string $resourceClass): void
    {
        $container = static::$kernel->getContainer();
        $registry = $this->isMongoDB() ? $container->get('doctrine_mongodb') : $container->get('doctrine');
        $manager = $registry->getManager();
        $s = new $resourceClass();
        $s->setTitle('Secured Dummy 1');
        $s->setDescription('Description 1');
        $s->setAdminOnlyProperty('admin secret');
        $s->setOwnerOnlyProperty('owner secret');
        $s->setAttributeBasedProperty('attribute based secret');
        $s->setOwner('user1');

        $manager->persist($s);
        $manager->flush();
    }

    public function testQueryCollection(): void
    {
        $resource = $this->isMongoDB() ? DocumentSecuredDummyCollection::class : SecuredDummyCollection::class;
        $this->recreateSchema([$resource, $resource.'Parent']);
        $this->loadFixturesQueryCollection($resource);
        $client = self::createClient();

        $response = $client->request('POST', '/graphql', ['headers' => ['Authorization' => 'Basic ZHVuZ2xhczprZXZpbg=='], 'json' => [
            'query' => <<<QUERY
    {
        securedDummyCollectionParents {
            edges {
              node {
               child {
                  title, ownerOnlyProperty, owner
                }
              }
            }
        }
    }
QUERY,
        ]]);

        $d = $response->toArray();
        $this->assertNull($d['data']['securedDummyCollectionParents']['edges'][1]['node']['child']['ownerOnlyProperty']);
    }

    public function loadFixturesQueryCollection(string $resourceClass): void
    {
        $parentResourceClass = $resourceClass.'Parent';
        $container = static::$kernel->getContainer();
        $registry = $this->isMongoDB() ? $container->get('doctrine_mongodb') : $container->get('doctrine');
        $manager = $registry->getManager();
        $s = new $resourceClass();
        $s->title = 'Foo';
        $s->ownerOnlyProperty = 'Foo by dunglas';
        $s->owner = 'dunglas';
        $manager->persist($s);
        $p = new $parentResourceClass();
        $p->child = $s;
        $manager->persist($p);
        $s = new $resourceClass();
        $s->title = 'Bar';
        $s->ownerOnlyProperty = 'Bar by admin';
        $s->owner = 'admin';
        $manager->persist($s);
        $p = new $parentResourceClass();
        $p->child = $s;
        $manager->persist($p);
        $s = new $resourceClass();
        $s->title = 'Baz';
        $s->ownerOnlyProperty = 'Baz by dunglas';
        $s->owner = 'dunglas';
        $manager->persist($s);
        $p = new $parentResourceClass();
        $p->child = $s;
        $manager->persist($p);
        $s = new $resourceClass();
        $s->ownerOnlyProperty = 'Bat by admin';
        $s->owner = 'admin';
        $s->title = 'Bat';
        $manager->persist($s);
        $p = new $parentResourceClass();
        $p->child = $s;
        $manager->persist($p);
        $manager->flush();
    }
}
