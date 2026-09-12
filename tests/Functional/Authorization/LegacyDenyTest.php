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

namespace ApiPlatform\Tests\Functional\Authorization;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\LegacySecuredDummy;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use Symfony\Component\Security\Core\User\InMemoryUser;

final class LegacyDenyTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [LegacySecuredDummy::class];
    }

    public function testAnonymousGetCollectionReturns401(): void
    {
        self::createClient()->request('GET', '/legacy_secured_dummies', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);
        $this->assertResponseStatusCodeSame(401);
    }

    public function testAuthenticatedUserGetCollectionReturns200(): void
    {
        $this->recreateSchema([LegacySecuredDummy::class]);
        $client = self::createClient();
        $client->loginUser(new InMemoryUser('dunglas', 'kevin', ['ROLE_USER']));
        $client->request('GET', '/legacy_secured_dummies', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json; charset=utf-8');
    }

    public function testStandardUserCannotCreate(): void
    {
        $client = self::createClient();
        $client->loginUser(new InMemoryUser('dunglas', 'kevin', ['ROLE_USER']));
        $client->request('POST', '/legacy_secured_dummies', [
            'headers' => [
                'Accept' => 'application/ld+json',
                'Content-Type' => 'application/ld+json',
            ],
            'json' => ['title' => 'Title', 'description' => 'Description', 'owner' => 'foo'],
        ]);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testAdminCanCreate(): void
    {
        $this->recreateSchema([LegacySecuredDummy::class]);
        $client = self::createClient();
        $client->loginUser(new InMemoryUser('admin', 'kitten', ['ROLE_ADMIN']));
        $client->request('POST', '/legacy_secured_dummies', [
            'headers' => [
                'Accept' => 'application/ld+json',
                'Content-Type' => 'application/ld+json',
            ],
            'json' => ['title' => 'Title', 'description' => 'Description', 'owner' => 'someone'],
        ]);
        $this->assertResponseStatusCodeSame(201);
    }

    public function testUserCannotGetItemTheyDontOwn(): void
    {
        $this->recreateSchema([LegacySecuredDummy::class]);
        $iri = $this->createLegacySecuredDummy(owner: 'someone');

        $client = self::createClient();
        $client->loginUser(new InMemoryUser('dunglas', 'kevin', ['ROLE_USER']));
        $client->request('GET', $iri, [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testUserCanGetItemTheyOwn(): void
    {
        $this->recreateSchema([LegacySecuredDummy::class]);
        $iri = $this->createLegacySecuredDummy(owner: 'dunglas');

        $client = self::createClient();
        $client->loginUser(new InMemoryUser('dunglas', 'kevin', ['ROLE_USER']));
        $client->request('GET', $iri, [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);
        $this->assertResponseIsSuccessful();
    }

    public function testUserCannotReassignItem(): void
    {
        $this->recreateSchema([LegacySecuredDummy::class]);
        $iri = $this->createLegacySecuredDummy(owner: 'dunglas');

        $client = self::createClient();
        $client->loginUser(new InMemoryUser('admin', 'kitten', ['ROLE_ADMIN']));
        $client->request('PUT', $iri, [
            'headers' => [
                'Accept' => 'application/ld+json',
                'Content-Type' => 'application/ld+json',
            ],
            'json' => ['owner' => 'kitten'],
        ]);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testUserCanTransferItemTheyOwn(): void
    {
        $this->recreateSchema([LegacySecuredDummy::class]);
        $iri = $this->createLegacySecuredDummy(owner: 'dunglas');

        $client = self::createClient();
        $client->loginUser(new InMemoryUser('dunglas', 'kevin', ['ROLE_USER']));
        $client->request('PUT', $iri, [
            'headers' => [
                'Accept' => 'application/ld+json',
                'Content-Type' => 'application/ld+json',
            ],
            'json' => ['owner' => 'vincent'],
        ]);
        $this->assertResponseIsSuccessful();
    }

    /**
     * Avoids hard-coding id=1, which is flaky on MongoDB ODM (INCREMENT counter
     * survives collection drops).
     */
    private function createLegacySecuredDummy(string $owner): string
    {
        $admin = self::createClient();
        $admin->loginUser(new InMemoryUser('admin', 'kitten', ['ROLE_ADMIN']));
        $response = $admin->request('POST', '/legacy_secured_dummies', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['title' => '#1', 'description' => '', 'owner' => $owner],
        ]);
        $this->assertResponseStatusCodeSame(201);

        return $response->toArray()['@id'];
    }
}
