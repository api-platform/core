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
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\SecuredDummy;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class SecurityTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [SecuredDummy::class];
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
}
