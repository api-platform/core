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

namespace ApiPlatform\Tests\Functional\Parameters;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue7916\UserActionResource;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue7916\UserActionResourceOdm;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue7916\UserResource;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue7916\UserResourceOdm;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\Issue7916\User as DocumentUser;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\Issue7916\UserAction as DocumentUserAction;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue7916\User;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue7916\UserAction;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

/**
 * Tests for issue #7916: Nested property filters should work on relations
 * to non-ApiResource entities.
 *
 * @see https://github.com/api-platform/core/issues/7916
 *
 * @group issue-7916
 */
final class Issue7916NestedFilterOnNonResourceTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        if ('mongodb' === static::getContainer()->getParameter('kernel.environment')) {
            return [UserActionResourceOdm::class, UserResourceOdm::class];
        }

        return [UserActionResource::class, UserResource::class];
    }

    protected function setUp(): void
    {
        $entities = $this->isMongoDB()
            ? [DocumentUserAction::class, DocumentUser::class]
            : [UserAction::class, User::class];

        $this->recreateSchema($entities);
        $this->loadFixtures();
    }

    /**
     * Test filtering on user.name where User is NOT an ApiResource.
     * This was failing in #7916 with error:
     * "[Semantical Error] Class UserAction has no field or association named user.name".
     */
    public function testFilteringOnNonResourceRelationName(): void
    {
        $response = self::createClient()->request('GET', '/user-actions?name=john');
        $this->assertResponseIsSuccessful();

        $data = $response->toArray();
        $this->assertCount(1, $data['hydra:member']);
        $this->assertSame('login', $data['hydra:member'][0]['action']);
    }

    /**
     * Test filtering on user.email where User is NOT an ApiResource.
     */
    public function testFilteringOnNonResourceRelationEmail(): void
    {
        $response = self::createClient()->request('GET', '/user-actions?email=john@example.com');
        $this->assertResponseIsSuccessful();

        $data = $response->toArray();
        $this->assertCount(1, $data['hydra:member']);
        $this->assertSame('login', $data['hydra:member'][0]['action']);
    }

    /**
     * Test partial matching on user.name.
     */
    public function testPartialFilteringOnNonResourceRelation(): void
    {
        $response = self::createClient()->request('GET', '/user-actions?name=ane');
        $this->assertResponseIsSuccessful();

        $data = $response->toArray();
        $this->assertCount(1, $data['hydra:member']);
        $this->assertSame('logout', $data['hydra:member'][0]['action']);
    }

    /**
     * Test no match scenario.
     */
    public function testNoMatchFilteringOnNonResourceRelation(): void
    {
        $response = self::createClient()->request('GET', '/user-actions?name=nonexistent');
        $this->assertResponseIsSuccessful();

        $data = $response->toArray();
        $this->assertCount(0, $data['hydra:member']);
    }

    private function loadFixtures(): void
    {
        $manager = $this->getManager();
        $isMongoDB = $this->isMongoDB();

        $userClass = $isMongoDB ? DocumentUser::class : User::class;
        $actionClass = $isMongoDB ? DocumentUserAction::class : UserAction::class;

        // Create users (NOT ApiResources)
        $user1 = new $userClass();
        $user1->setName('john');
        $user1->setEmail('john@example.com');

        $user2 = new $userClass();
        $user2->setName('jane');
        $user2->setEmail('jane@example.com');

        $manager->persist($user1);
        $manager->persist($user2);
        $manager->flush();

        // Create user actions
        $action1 = new $actionClass();
        $action1->setAction('login');
        $action1->setUser($user1);

        $action2 = new $actionClass();
        $action2->setAction('logout');
        $action2->setUser($user2);

        $manager->persist($action1);
        $manager->persist($action2);
        $manager->flush();
    }
}
