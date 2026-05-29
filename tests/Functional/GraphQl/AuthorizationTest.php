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

use ApiPlatform\GraphQl\Test\GraphQlTestTrait;
use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\RelatedDummy as RelatedDummyDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\RelatedLinkedDummy as RelatedLinkedDummyDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\RelatedSecuredDummy as RelatedSecuredDummyDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\SecuredDummy as SecuredDummyDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelatedDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelatedLinkedDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelatedSecuredDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\SecuredDummy;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class AuthorizationTest extends ApiTestCase
{
    use GraphQlTestTrait;
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    private const ADMIN_AUTH = 'Basic YWRtaW46a2l0dGVu';
    private const DUNGLAS_AUTH = 'Basic ZHVuZ2xhczprZXZpbg==';

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [
            SecuredDummy::class,
            RelatedDummy::class,
            RelatedSecuredDummy::class,
            RelatedLinkedDummy::class,
        ];
    }

    public function testAnonymousCannotReadSecuredItem(): void
    {
        $this->recreateAuthSchema();
        $this->seedSecuredDummies(1);

        $response = $this->executeGraphQl(<<<'QUERY'
            {
              securedDummy(id: "/secured_dummies/1") {
                title
                description
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray(false);
        $this->assertSame(403, $data['errors'][0]['extensions']['status']);
        $this->assertSame('Access Denied.', $data['errors'][0]['message']);
        $this->assertNull($data['data']['securedDummy']);
    }

    public function testAnonymousCannotReadSecuredCollection(): void
    {
        $this->recreateAuthSchema();
        $this->seedSecuredDummies(1);

        $response = $this->executeGraphQl(<<<'QUERY'
            {
              securedDummies {
                edges { node { title description } }
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray(false);
        $this->assertSame(403, $data['errors'][0]['extensions']['status']);
        $this->assertSame('Access Denied.', $data['errors'][0]['message']);
        $this->assertNull($data['data']['securedDummies']);
    }

    public function testAdminCanReadSecuredCollection(): void
    {
        $this->recreateAuthSchema();
        $this->seedSecuredDummies(1);

        $response = $this->executeGraphQl(<<<'QUERY'
            {
              securedDummies {
                edges { node { title description } }
              }
            }
            QUERY, headers: ['Authorization' => self::ADMIN_AUTH]);

        $this->assertResponseIsSuccessful();
        $this->assertNotNull($response->toArray()['data']['securedDummies']);
    }

    public function testUserCannotReadSecuredCollection(): void
    {
        $this->recreateAuthSchema();
        $this->seedSecuredDummies(1);

        $response = $this->executeGraphQl(<<<'QUERY'
            {
              securedDummies {
                edges { node { title description } }
              }
            }
            QUERY, headers: ['Authorization' => self::DUNGLAS_AUTH]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray(false);
        $this->assertNull($data['data']['securedDummies']);
        $this->assertSame(403, $data['errors'][0]['extensions']['status']);
        $this->assertSame('Access Denied.', $data['errors'][0]['message']);
    }

    public function testAnonymousCannotCreateSecuredResource(): void
    {
        $this->recreateAuthSchema();

        $response = $this->executeGraphQl(<<<'QUERY'
            mutation {
              createSecuredDummy(input: {owner: "me", title: "Hi", description: "Desc", adminOnlyProperty: "secret", clientMutationId: "auth"}) {
                securedDummy {
                  title
                  owner
                }
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray(false);
        $this->assertSame(403, $data['errors'][0]['extensions']['status']);
        $this->assertSame('Only admins can create a secured dummy.', $data['errors'][0]['message']);
        $this->assertNull($data['data']['createSecuredDummy']);
    }

    public function testAdminCanAccessSecuredRelationsOwnedByAdmin(): void
    {
        $this->recreateAuthSchema();
        $this->seedSecuredDummiesWithRelations(1, 'admin');

        $response = $this->executeGraphQl(<<<'QUERY'
            {
              securedDummy(id: "/secured_dummies/1") {
                relatedDummies { edges { node { id } } }
                relatedDummy { id }
                relatedSecuredDummies { edges { node { id } } }
                relatedSecuredDummy { id }
                publicRelatedSecuredDummies { edges { node { id } } }
                publicRelatedSecuredDummy { id }
              }
            }
            QUERY, headers: ['Authorization' => self::ADMIN_AUTH]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray()['data']['securedDummy'];
        $this->assertCount(1, $data['relatedDummies']['edges']);
        $this->assertNotNull($data['relatedDummy']);
        $this->assertCount(1, $data['relatedSecuredDummies']['edges']);
        $this->assertNotNull($data['relatedSecuredDummy']);
        $this->assertCount(1, $data['publicRelatedSecuredDummies']['edges']);
        $this->assertNotNull($data['publicRelatedSecuredDummy']);
    }

    public function testUserCannotReadSecuredCollectionRelationOnSecuredItemTheyDoNotOwn(): void
    {
        $this->recreateAuthSchema();
        $this->seedSecuredDummiesWithRelations(1, 'someone-else');

        $response = $this->executeGraphQl(<<<'QUERY'
            {
              securedDummy(id: "/secured_dummies/1") {
                relatedDummies { edges { node { id } } }
                relatedDummy { id }
              }
            }
            QUERY, headers: ['Authorization' => self::DUNGLAS_AUTH]);

        $this->assertResponseIsSuccessful();
        $secured = $response->toArray(false)['data']['securedDummy'];
        $this->assertNull($secured['relatedDummies']);
        $this->assertNull($secured['relatedDummy']);
    }

    public function testUserCannotAccessRelatedSecuredDummyDirectly(): void
    {
        $this->recreateAuthSchema();
        $this->seedSecuredDummiesWithRelations(1, 'dunglas');

        $response = $this->executeGraphQl(<<<'QUERY'
            {
              relatedSecuredDummy(id: "/related_secured_dummies/1") {
                id
              }
            }
            QUERY, headers: ['Authorization' => self::DUNGLAS_AUTH]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray(false);
        $this->assertSame(403, $data['errors'][0]['extensions']['status']);
        $this->assertSame('Access Denied.', $data['errors'][0]['message']);
        $this->assertNull($data['data']['relatedSecuredDummy']);
    }

    public function testUserCannotListRelatedSecuredDummies(): void
    {
        $this->recreateAuthSchema();
        $this->seedSecuredDummiesWithRelations(1, 'dunglas');

        $response = $this->executeGraphQl(<<<'QUERY'
            {
              relatedSecuredDummies {
                edges { node { id } }
              }
            }
            QUERY, headers: ['Authorization' => self::DUNGLAS_AUTH]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray(false);
        $this->assertSame(403, $data['errors'][0]['extensions']['status']);
        $this->assertSame('Access Denied.', $data['errors'][0]['message']);
        $this->assertNull($data['data']['relatedSecuredDummies']);
    }

    public function testUserCanAccessSecuredRelationsOnOwnedDummy(): void
    {
        $this->recreateAuthSchema();
        $this->seedSecuredDummiesWithRelations(1, 'dunglas');

        $response = $this->executeGraphQl(<<<'QUERY'
            {
              securedDummy(id: "/secured_dummies/1") {
                relatedSecuredDummies { edges { node { id } } }
                relatedSecuredDummy { id }
                publicRelatedSecuredDummies { edges { node { id } } }
                publicRelatedSecuredDummy { id }
              }
            }
            QUERY, headers: ['Authorization' => self::DUNGLAS_AUTH]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray()['data']['securedDummy'];
        $this->assertCount(1, $data['relatedSecuredDummies']['edges']);
        $this->assertNotNull($data['relatedSecuredDummy']);
        $this->assertCount(1, $data['publicRelatedSecuredDummies']['edges']);
        $this->assertNotNull($data['publicRelatedSecuredDummy']);
    }

    public function testAdminCanCreateSecuredResource(): void
    {
        $this->recreateAuthSchema();

        $response = $this->executeGraphQl(<<<'QUERY'
            mutation {
              createSecuredDummy(input: {owner: "someone", title: "Hi", description: "Desc", adminOnlyProperty: "secret"}) {
                securedDummy {
                  id
                  title
                  owner
                }
              }
            }
            QUERY, headers: ['Authorization' => self::ADMIN_AUTH]);

        $this->assertResponseIsSuccessful();
        $this->assertSame('someone', $response->toArray()['data']['createSecuredDummy']['securedDummy']['owner']);
    }

    public function testAdminCanCreateOwnerOnlyPropertyWhenAdminIsOwner(): void
    {
        $this->recreateAuthSchema();

        $response = $this->executeGraphQl(<<<'QUERY'
            mutation {
              createSecuredDummy(input: {owner: "admin", title: "Hi", description: "Desc", adminOnlyProperty: "secret", ownerOnlyProperty: "it works"}) {
                securedDummy {
                  ownerOnlyProperty
                }
              }
            }
            QUERY, headers: ['Authorization' => self::ADMIN_AUTH]);

        $this->assertResponseIsSuccessful();
        $this->assertSame('it works', $response->toArray()['data']['createSecuredDummy']['securedDummy']['ownerOnlyProperty']);
    }

    public function testAdminCannotSetOwnerOnlyPropertyWhenNotOwner(): void
    {
        $this->recreateAuthSchema();

        $response = $this->executeGraphQl(<<<'QUERY'
            mutation {
              createSecuredDummy(input: {owner: "dunglas", title: "Hi", description: "Desc", adminOnlyProperty: "secret", ownerOnlyProperty: "should not be set"}) {
                securedDummy {
                  ownerOnlyProperty
                }
              }
            }
            QUERY, headers: ['Authorization' => self::ADMIN_AUTH]);

        $this->assertResponseIsSuccessful();
        $this->assertNull($response->toArray()['data']['createSecuredDummy']['securedDummy']['ownerOnlyProperty']);
    }

    public function testUserCannotReadItemTheyDoNotOwn(): void
    {
        $this->recreateAuthSchema();
        $this->seedSecuredDummyWithOwner('admin');

        $response = $this->executeGraphQl(<<<'QUERY'
            {
              securedDummy(id: "/secured_dummies/1") {
                owner
                title
              }
            }
            QUERY, headers: ['Authorization' => self::DUNGLAS_AUTH]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray(false);
        $this->assertSame(403, $data['errors'][0]['extensions']['status']);
        $this->assertSame('Access Denied.', $data['errors'][0]['message']);
        $this->assertNull($data['data']['securedDummy']);
    }

    public function testUserCanReadItemTheyOwn(): void
    {
        $this->recreateAuthSchema();
        $this->seedSecuredDummyWithOwner('dunglas');

        $response = $this->executeGraphQl(<<<'QUERY'
            {
              securedDummy(id: "/secured_dummies/1") {
                owner
                title
              }
            }
            QUERY, headers: ['Authorization' => self::DUNGLAS_AUTH]);

        $this->assertResponseIsSuccessful();
        $this->assertSame('dunglas', $response->toArray()['data']['securedDummy']['owner']);
    }

    public function testAdminCanReadAdminOnlyPropertyOnOtherUsersItem(): void
    {
        $this->recreateAuthSchema();
        $this->seedSecuredDummyWithOwner('dunglas', adminProperty: 'admin secret');

        $response = $this->executeGraphQl(<<<'QUERY'
            {
              securedDummy(id: "/secured_dummies/1") {
                owner
                title
                adminOnlyProperty
              }
            }
            QUERY, headers: ['Authorization' => self::ADMIN_AUTH]);

        $this->assertResponseIsSuccessful();
        $this->assertSame('admin secret', $response->toArray()['data']['securedDummy']['adminOnlyProperty']);
    }

    public function testUserCannotReadAdminOnlyPropertyOnOwnedItem(): void
    {
        $this->recreateAuthSchema();
        $this->seedSecuredDummyWithOwner('dunglas', adminProperty: 'admin secret');

        $response = $this->executeGraphQl(<<<'QUERY'
            {
              securedDummy(id: "/secured_dummies/1") {
                owner
                title
                adminOnlyProperty
              }
            }
            QUERY, headers: ['Authorization' => self::DUNGLAS_AUTH]);

        $this->assertResponseIsSuccessful();
        $this->assertNull($response->toArray()['data']['securedDummy']['adminOnlyProperty']);
    }

    public function testUserCanReadOwnerOnlyPropertyOnOwnedItem(): void
    {
        $this->recreateAuthSchema();
        $this->seedSecuredDummyWithOwner('dunglas', ownerProperty: 'owner secret');

        $response = $this->executeGraphQl(<<<'QUERY'
            {
              securedDummy(id: "/secured_dummies/1") {
                ownerOnlyProperty
              }
            }
            QUERY, headers: ['Authorization' => self::DUNGLAS_AUTH]);

        $this->assertResponseIsSuccessful();
        $this->assertSame('owner secret', $response->toArray()['data']['securedDummy']['ownerOnlyProperty']);
    }

    public function testUserCanUpdateOwnerOnlyPropertyOnOwnedItem(): void
    {
        $this->recreateAuthSchema();
        $this->seedSecuredDummyWithOwner('dunglas', ownerProperty: 'original');

        $response = $this->executeGraphQl(<<<'QUERY'
            mutation {
              updateSecuredDummy(input: {id: "/secured_dummies/1", ownerOnlyProperty: "updated"}) {
                securedDummy {
                  ownerOnlyProperty
                }
              }
            }
            QUERY, headers: ['Authorization' => self::DUNGLAS_AUTH]);

        $this->assertResponseIsSuccessful();
        $this->assertSame('updated', $response->toArray()['data']['updateSecuredDummy']['securedDummy']['ownerOnlyProperty']);
    }

    public function testAdminCannotReadOwnerOnlyPropertyOnOtherUsersItem(): void
    {
        $this->recreateAuthSchema();
        $this->seedSecuredDummyWithOwner('dunglas', ownerProperty: 'owner secret');

        $response = $this->executeGraphQl(<<<'QUERY'
            {
              securedDummy(id: "/secured_dummies/1") {
                ownerOnlyProperty
              }
            }
            QUERY, headers: ['Authorization' => self::ADMIN_AUTH]);

        $this->assertResponseIsSuccessful();
        $this->assertNull($response->toArray()['data']['securedDummy']['ownerOnlyProperty']);
    }

    public function testUserCannotAssignItemTheyDoNotOwnToThemselves(): void
    {
        $this->recreateAuthSchema();
        $this->seedSecuredDummyWithOwner('someone');

        $response = $this->executeGraphQl(<<<'QUERY'
            mutation {
              updateSecuredDummy(input: {id: "/secured_dummies/1", owner: "kitten"}) {
                securedDummy { id title owner }
              }
            }
            QUERY, headers: ['Authorization' => self::ADMIN_AUTH]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray(false);
        $this->assertSame(403, $data['errors'][0]['extensions']['status']);
        $this->assertSame('Access Denied.', $data['errors'][0]['message']);
        $this->assertNull($data['data']['updateSecuredDummy']);
    }

    public function testUserCanTransferOwnedItem(): void
    {
        $this->recreateAuthSchema();
        $this->seedSecuredDummyWithOwner('dunglas');

        $response = $this->executeGraphQl(<<<'QUERY'
            mutation {
              updateSecuredDummy(input: {id: "/secured_dummies/1", owner: "vincent"}) {
                securedDummy { id title owner }
              }
            }
            QUERY, headers: ['Authorization' => self::DUNGLAS_AUTH]);

        $this->assertResponseIsSuccessful();
        $this->assertSame('vincent', $response->toArray()['data']['updateSecuredDummy']['securedDummy']['owner']);
    }

    private function recreateAuthSchema(): void
    {
        $this->recreateSchema([
            $this->isMongoDB() ? SecuredDummyDocument::class : SecuredDummy::class,
            $this->isMongoDB() ? RelatedDummyDocument::class : RelatedDummy::class,
            $this->isMongoDB() ? RelatedSecuredDummyDocument::class : RelatedSecuredDummy::class,
            $this->isMongoDB() ? RelatedLinkedDummyDocument::class : RelatedLinkedDummy::class,
        ]);
    }

    private function newSecuredDummy(): object
    {
        $class = $this->isMongoDB() ? SecuredDummyDocument::class : SecuredDummy::class;

        return new $class();
    }

    private function newRelatedDummy(): object
    {
        $class = $this->isMongoDB() ? RelatedDummyDocument::class : RelatedDummy::class;

        return new $class();
    }

    private function newRelatedSecuredDummy(): object
    {
        $class = $this->isMongoDB() ? RelatedSecuredDummyDocument::class : RelatedSecuredDummy::class;

        return new $class();
    }

    private function newRelatedLinkedDummy(): object
    {
        $class = $this->isMongoDB() ? RelatedLinkedDummyDocument::class : RelatedLinkedDummy::class;

        return new $class();
    }

    private function seedSecuredDummies(int $count): void
    {
        $manager = $this->getManager();
        for ($i = 1; $i <= $count; ++$i) {
            $d = $this->newSecuredDummy();
            $d->setTitle("#$i");
            $d->setDescription("Hello #$i");
            $d->setOwner('notexist');
            $manager->persist($d);
        }
        $manager->flush();
    }

    private function seedSecuredDummyWithOwner(string $owner, ?string $adminProperty = null, ?string $ownerProperty = null): void
    {
        $manager = $this->getManager();
        $d = $this->newSecuredDummy();
        $d->setTitle('#1');
        $d->setDescription('Hello #1');
        $d->setOwner($owner);
        if (null !== $adminProperty) {
            $d->setAdminOnlyProperty($adminProperty);
        }
        if (null !== $ownerProperty) {
            $d->setOwnerOnlyProperty($ownerProperty);
        }
        $manager->persist($d);
        $manager->flush();
    }

    private function seedSecuredDummiesWithRelations(int $count, string $owner): void
    {
        $manager = $this->getManager();
        for ($i = 1; $i <= $count; ++$i) {
            $secured = $this->newSecuredDummy();
            $secured->setTitle("#$i");
            $secured->setDescription("Hello #$i");
            $secured->setOwner($owner);

            $related = $this->newRelatedDummy();
            $related->setName('RelatedDummy');
            $manager->persist($related);

            $relatedSecured = $this->newRelatedSecuredDummy();
            $manager->persist($relatedSecured);

            $publicRelated = $this->newRelatedSecuredDummy();
            $manager->persist($publicRelated);

            $linked = $this->newRelatedLinkedDummy();
            $manager->persist($linked);

            $secured->addRelatedDummy($related);
            $secured->setRelatedDummy($related);
            $secured->addRelatedSecuredDummy($relatedSecured);
            $secured->setRelatedSecuredDummy($relatedSecured);
            $secured->addPublicRelatedSecuredDummy($publicRelated);
            $secured->setPublicRelatedSecuredDummy($publicRelated);
            $linked->setSecuredDummy($secured);

            $manager->persist($secured);
        }
        $manager->flush();
    }
}
