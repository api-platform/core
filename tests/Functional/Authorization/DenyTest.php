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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue6446\SecurityPostValidation;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelatedLinkedDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\SecuredDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\SecuredDummyWithPropertiesDependingOnThemselves;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use Symfony\Component\Security\Core\User\InMemoryUser;

final class DenyTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [
            SecuredDummy::class,
            SecuredDummyWithPropertiesDependingOnThemselves::class,
            RelatedLinkedDummy::class,
            SecurityPostValidation::class,
        ];
    }

    public function testAnonymousGetCollectionReturns401(): void
    {
        self::createClient()->request('GET', '/secured_dummies', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);
        $this->assertResponseStatusCodeSame(401);
    }

    public function testAuthenticatedUserGetCollectionReturns200(): void
    {
        $this->recreateSchema([SecuredDummy::class]);
        $client = self::createClient();
        $client->loginUser(new InMemoryUser('dunglas', 'kevin', ['ROLE_USER']));
        $client->request('GET', '/secured_dummies', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json; charset=utf-8');
    }

    public function testCustomDataProviderGeneratorReturns200(): void
    {
        $this->recreateSchema([SecuredDummy::class]);
        $client = self::createClient();
        $client->loginUser(new InMemoryUser('dunglas', 'kevin', ['ROLE_USER']));
        $client->request('GET', '/custom_data_provider_generator', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);
        $this->assertResponseIsSuccessful();
    }

    public function testStandardUserCannotCreate(): void
    {
        $client = self::createClient();
        $client->loginUser(new InMemoryUser('dunglas', 'kevin', ['ROLE_USER']));
        $client->request('POST', '/secured_dummies', [
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
        $this->recreateSchema([SecuredDummy::class]);
        $client = self::createClient();
        $client->loginUser(new InMemoryUser('admin', 'kitten', ['ROLE_ADMIN']));
        $client->request('POST', '/secured_dummies', [
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
        $this->recreateSchema([SecuredDummy::class]);
        $iri = $this->createSecuredDummy(owner: 'someone');

        $client = self::createClient();
        $client->loginUser(new InMemoryUser('dunglas', 'kevin', ['ROLE_USER']));
        $client->request('GET', $iri, [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testUserCanGetItemTheyOwn(): void
    {
        $this->recreateSchema([SecuredDummy::class]);
        $iri = $this->createSecuredDummy(owner: 'dunglas');

        $client = self::createClient();
        $client->loginUser(new InMemoryUser('dunglas', 'kevin', ['ROLE_USER']));
        $client->request('GET', $iri, [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);
        $this->assertResponseIsSuccessful();
    }

    public function testOwnerSeesOwnerOnlyAndAttributeBasedProperties(): void
    {
        $this->recreateSchema([SecuredDummy::class]);
        $iri = $this->createSecuredDummy(owner: 'dunglas');

        $client = self::createClient();
        $client->loginUser(new InMemoryUser('dunglas', 'kevin', ['ROLE_USER']));
        $response = $client->request('GET', $iri, [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertArrayHasKey('ownerOnlyProperty', $body);
        $this->assertNotNull($body['ownerOnlyProperty']);
        $this->assertArrayHasKey('attributeBasedProperty', $body);
        $this->assertNotNull($body['attributeBasedProperty']);
    }

    public function testAdminCanCreateWithPropertiesDependingOnThemselves(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped();
        }

        $this->recreateSchema([SecuredDummyWithPropertiesDependingOnThemselves::class]);
        $client = self::createClient();
        $client->loginUser(new InMemoryUser('admin', 'kitten', ['ROLE_ADMIN']));
        $client->request('POST', '/secured_dummy_with_properties_depending_on_themselves', [
            'headers' => [
                'Accept' => 'application/ld+json',
                'Content-Type' => 'application/ld+json',
            ],
            'json' => ['canUpdateProperty' => false, 'property' => false],
        ]);
        $this->assertResponseStatusCodeSame(201);
    }

    public function testCannotPatchSecuredPropertyIfNotGranted(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped();
        }

        $this->recreateSchema([SecuredDummyWithPropertiesDependingOnThemselves::class]);
        $admin = self::createClient();
        $admin->loginUser(new InMemoryUser('admin', 'kitten', ['ROLE_ADMIN']));
        $admin->request('POST', '/secured_dummy_with_properties_depending_on_themselves', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['canUpdateProperty' => false, 'property' => false],
        ]);
        $this->assertResponseStatusCodeSame(201);

        $client = self::createClient();
        $client->loginUser(new InMemoryUser('admin', 'kitten', ['ROLE_ADMIN']));
        $response = $client->request('PATCH', '/secured_dummy_with_properties_depending_on_themselves/1', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['canUpdateProperty' => true, 'property' => true],
        ]);
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertTrue($body['canUpdateProperty']);
        $this->assertFalse($body['property']);
    }

    public function testAdminCannotSeeOwnerOnlyPropertiesOnOthersItems(): void
    {
        $this->recreateSchema([SecuredDummy::class]);
        $admin1 = self::createClient();
        $admin1->loginUser(new InMemoryUser('admin', 'kitten', ['ROLE_ADMIN']));
        $admin1->request('POST', '/secured_dummies', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['title' => '#1', 'owner' => 'someone'],
        ]);
        $this->assertResponseStatusCodeSame(201);
        $admin2 = self::createClient();
        $admin2->loginUser(new InMemoryUser('admin', 'kitten', ['ROLE_ADMIN']));
        $admin2->request('POST', '/secured_dummies', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['title' => '#2', 'owner' => 'dunglas'],
        ]);
        $this->assertResponseStatusCodeSame(201);

        $client = self::createClient();
        $client->loginUser(new InMemoryUser('admin', 'kitten', ['ROLE_ADMIN']));
        $response = $client->request('GET', '/secured_dummies', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertStringNotContainsString('ownerOnlyProperty', $response->getContent());
        $this->assertStringNotContainsString('attributeBasedProperty', $response->getContent());
    }

    public function testUserCannotReassignItem(): void
    {
        $this->recreateSchema([SecuredDummy::class]);
        $iri = $this->createSecuredDummy(owner: 'dunglas');

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
        $this->recreateSchema([SecuredDummy::class]);
        $iri = $this->createSecuredDummy(owner: 'dunglas');

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

    public function testAdminSeesAdminOnlyProperty(): void
    {
        $this->recreateSchema([SecuredDummy::class]);
        $admin = self::createClient();
        $admin->loginUser(new InMemoryUser('admin', 'kitten', ['ROLE_ADMIN']));
        $admin->request('POST', '/secured_dummies', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['title' => '#1', 'owner' => 'dunglas', 'adminOnlyProperty' => 'secret'],
        ]);
        $this->assertResponseStatusCodeSame(201);

        $client = self::createClient();
        $client->loginUser(new InMemoryUser('admin', 'kitten', ['ROLE_ADMIN']));
        $response = $client->request('GET', '/secured_dummies', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('adminOnlyProperty', $response->getContent());
    }

    public function testUserDoesNotSeeAdminOnlyProperty(): void
    {
        $this->recreateSchema([SecuredDummy::class]);
        $admin = self::createClient();
        $admin->loginUser(new InMemoryUser('admin', 'kitten', ['ROLE_ADMIN']));
        $admin->request('POST', '/secured_dummies', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['title' => '#1', 'owner' => 'someone', 'adminOnlyProperty' => 'secret'],
        ]);
        $this->assertResponseStatusCodeSame(201);

        $client = self::createClient();
        $client->loginUser(new InMemoryUser('dunglas', 'kevin', ['ROLE_USER']));
        $response = $client->request('GET', '/secured_dummies', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertStringNotContainsString('adminOnlyProperty', $response->getContent());
    }

    public function testAdminCanCreateWithAdminOnlyProperty(): void
    {
        $this->recreateSchema([SecuredDummy::class]);
        $client = self::createClient();
        $client->loginUser(new InMemoryUser('admin', 'kitten', ['ROLE_ADMIN']));
        $response = $client->request('POST', '/secured_dummies', [
            'headers' => [
                'Accept' => 'application/ld+json',
                'Content-Type' => 'application/ld+json',
            ],
            'json' => [
                'title' => 'Common Title',
                'description' => 'Description',
                'owner' => 'dunglas',
                'adminOnlyProperty' => 'Is it safe?',
            ],
        ]);
        $this->assertResponseStatusCodeSame(201);
        $body = $response->toArray();
        $this->assertStringContainsString('adminOnlyProperty', $response->getContent());
        $this->assertSame('Is it safe?', $body['adminOnlyProperty']);
    }

    public function testUserCannotUpdateAdminOnlyProperty(): void
    {
        $this->recreateSchema([SecuredDummy::class]);
        $iri = $this->createSecuredDummy(
            owner: 'dunglas',
            extra: [
                'title' => 'Common Title',
                'description' => 'Description',
                'adminOnlyProperty' => 'Is it safe?',
            ],
        );

        $client = self::createClient();
        $client->loginUser(new InMemoryUser('dunglas', 'kevin', ['ROLE_USER']));
        $response = $client->request('PUT', $iri, [
            'headers' => [
                'Accept' => 'application/ld+json',
                'Content-Type' => 'application/ld+json',
            ],
            'json' => ['adminOnlyProperty' => 'Yes it is!'],
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertStringNotContainsString('adminOnlyProperty', $response->getContent());

        $adminClient = self::createClient();
        $adminClient->loginUser(new InMemoryUser('admin', 'kitten', ['ROLE_ADMIN']));
        $listResponse = $adminClient->request('GET', '/secured_dummies', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $listBody = $listResponse->toArray();
        $this->assertSame('Is it safe?', $listBody['hydra:member'][0]['adminOnlyProperty']);
    }

    public function testUserCanUpdateOwnerOnlyAndAttributeBasedProperties(): void
    {
        $this->recreateSchema([SecuredDummy::class]);
        $iri = $this->createSecuredDummy(owner: 'dunglas');

        $client = self::createClient();
        $client->loginUser(new InMemoryUser('dunglas', 'kevin', ['ROLE_USER']));
        $response = $client->request('PUT', $iri, [
            'headers' => [
                'Accept' => 'application/ld+json',
                'Content-Type' => 'application/ld+json',
            ],
            'json' => ['ownerOnlyProperty' => 'updated', 'attributeBasedProperty' => 'updated'],
        ]);
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertStringContainsString('ownerOnlyProperty', $response->getContent());
        $this->assertSame('updated', $body['ownerOnlyProperty']);
        $this->assertSame('updated', $body['attributeBasedProperty']);
    }

    public function testLinkSecurityNotFoundReturns404(): void
    {
        $this->recreateSchema([SecuredDummy::class, RelatedLinkedDummy::class]);
        $client = self::createClient();
        $client->loginUser(new InMemoryUser('dunglas', 'kevin', ['ROLE_USER']));
        $client->request('GET', '/secured_dummies/40000/to_from', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testLinkSecurityToFromAuthorized(): void
    {
        [$securedId, $linkedId] = $this->seedLinkedDummy(owner: 'dunglas');

        $client = self::createClient();
        $client->loginUser(new InMemoryUser('dunglas', 'kevin', ['ROLE_USER']));
        $response = $client->request('GET', "/secured_dummies/{$securedId}/to_from", [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertStringContainsString('securedDummy', $response->getContent());
        $this->assertSame($linkedId, $body['hydra:member'][0]['id']);
    }

    public function testLinkSecurityWithNameAuthorized(): void
    {
        [$securedId, $linkedId] = $this->seedLinkedDummy(owner: 'dunglas');

        $client = self::createClient();
        $client->loginUser(new InMemoryUser('dunglas', 'kevin', ['ROLE_USER']));
        $response = $client->request('GET', "/secured_dummies/{$securedId}/with_name", [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertStringContainsString('securedDummy', $response->getContent());
        $this->assertSame($linkedId, $body['hydra:member'][0]['id']);
    }

    public function testLinkSecurityFromFromAuthorized(): void
    {
        [$securedId, $linkedId] = $this->seedLinkedDummy(owner: 'dunglas');

        $client = self::createClient();
        $client->loginUser(new InMemoryUser('dunglas', 'kevin', ['ROLE_USER']));
        $response = $client->request('GET', "/related_linked_dummies/{$linkedId}/from_from", [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertStringContainsString('id', $response->getContent());
        // The /related_linked_dummies/{relatedDummyId}/from_from operation
        // returns the linked SecuredDummy collection, not the relation itself.
        $this->assertSame($securedId, $body['hydra:member'][0]['id']);
    }

    public function testLinkSecurityMultipleLinksAuthorized(): void
    {
        [$securedId, $linkedId] = $this->seedLinkedDummy(owner: 'dunglas');

        $client = self::createClient();
        $client->loginUser(new InMemoryUser('dunglas', 'kevin', ['ROLE_USER']));
        $response = $client->request('GET', "/secured_dummies/{$securedId}/related/{$linkedId}", [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertStringContainsString('id', $response->getContent());
        $this->assertSame($linkedId, $body['hydra:member'][0]['id']);
    }

    public function testLinkSecurityToFromUnauthorized(): void
    {
        [$securedId] = $this->seedLinkedDummy(owner: 'someone');

        $client = self::createClient();
        $client->loginUser(new InMemoryUser('dunglas', 'kevin', ['ROLE_USER']));
        $client->request('GET', "/secured_dummies/{$securedId}/to_from", [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testLinkSecurityWithNameUnauthorized(): void
    {
        [$securedId] = $this->seedLinkedDummy(owner: 'someone');

        $client = self::createClient();
        $client->loginUser(new InMemoryUser('dunglas', 'kevin', ['ROLE_USER']));
        $client->request('GET', "/secured_dummies/{$securedId}/with_name", [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testLinkSecurityFromFromUnauthorized(): void
    {
        [, $linkedId] = $this->seedLinkedDummy(owner: 'someone');

        $client = self::createClient();
        $client->loginUser(new InMemoryUser('dunglas', 'kevin', ['ROLE_USER']));
        $client->request('GET', "/related_linked_dummies/{$linkedId}/from_from", [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testLinkSecurityMultipleLinksUnauthorized(): void
    {
        [$securedId, $linkedId] = $this->seedLinkedDummy(owner: 'someone');

        $client = self::createClient();
        $client->loginUser(new InMemoryUser('dunglas', 'kevin', ['ROLE_USER']));
        $client->request('GET', "/secured_dummies/{$securedId}/related/{$linkedId}", [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);
        $this->assertResponseStatusCodeSame(403);
    }

    /**
     * Admin-POSTs a SecuredDummy and returns its IRI. Avoids hard-coding id=1,
     * which is flaky on MongoDB ODM (INCREMENT counter survives collection drops).
     */
    private function createSecuredDummy(string $owner, array $extra = []): string
    {
        $admin = self::createClient();
        $admin->loginUser(new InMemoryUser('admin', 'kitten', ['ROLE_ADMIN']));
        $response = $admin->request('POST', '/secured_dummies', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['title' => '#1', 'owner' => $owner] + $extra,
        ]);
        $this->assertResponseStatusCodeSame(201);

        return $response->toArray()['@id'];
    }

    /**
     * Seeds one SecuredDummy + one RelatedLinkedDummy via the API so the same
     * helper works against either ORM or ODM persistence, and returns the
     * generated ids (parsed from the IRIs). Hard-coding id=1 is flaky.
     *
     * @return array{0:int, 1:int}
     */
    private function seedLinkedDummy(string $owner): array
    {
        $this->recreateSchema([SecuredDummy::class, RelatedLinkedDummy::class]);

        $admin = self::createClient();
        $admin->loginUser(new InMemoryUser('admin', 'kitten', ['ROLE_ADMIN']));
        $dummyResponse = $admin->request('POST', '/secured_dummies', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['title' => '#1', 'owner' => $owner],
        ]);
        $this->assertResponseStatusCodeSame(201);
        $securedIri = $dummyResponse->toArray()['@id'];

        $linkedResponse = $admin->request('POST', '/related_linked_dummies', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['securedDummy' => $securedIri],
        ]);
        $this->assertResponseStatusCodeSame(201);
        $linkedId = $linkedResponse->toArray()['id'];

        $securedId = (int) basename($securedIri);

        return [$securedId, (int) $linkedId];
    }

    public function testUserSeesOwnerOnlyPropertyWithJsonFormat(): void
    {
        $this->recreateSchema([SecuredDummy::class]);
        $admin = self::createClient();
        $admin->loginUser(new InMemoryUser('admin', 'kitten', ['ROLE_ADMIN']));
        $admin->request('POST', '/secured_dummies', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['title' => '#1', 'owner' => 'dunglas'],
        ]);
        $this->assertResponseStatusCodeSame(201);

        $client = self::createClient();
        $client->loginUser(new InMemoryUser('dunglas', 'kevin', ['ROLE_USER']));
        $response = $client->request('GET', '/secured_dummies', [
            'headers' => ['Accept' => 'application/json'],
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('ownerOnlyProperty', $response->getContent());
        $this->assertStringContainsString('attributeBasedProperty', $response->getContent());
    }

    public function testSecurityPostValidation(): void
    {
        $client = self::createClient();
        $client->loginUser(new InMemoryUser('dunglas', 'kevin', ['ROLE_USER']));
        $client->request('POST', '/issue_6446', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['title' => ''],
        ]);
        $this->assertResponseStatusCodeSame(403);
    }
}
