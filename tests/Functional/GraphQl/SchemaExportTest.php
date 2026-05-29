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
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyFriend;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\OptionalRequiredDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelatedToDummyFriend;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\ThirdLevel;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\ApplicationTester;

final class SchemaExportTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    private ApplicationTester $tester;

    protected function setUp(): void
    {
        self::bootKernel();

        $application = new Application(static::$kernel);
        $application->setCatchExceptions(false);
        $application->setAutoExit(false);
        $this->tester = new ApplicationTester($application);
    }

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [
            DummyFriend::class,
            RelatedToDummyFriend::class,
            OptionalRequiredDummy::class,
            ThirdLevel::class,
        ];
    }

    public function testExportGraphQlSchema(): void
    {
        $this->tester->run(['command' => 'api:graphql:export']);

        $output = $this->tester->getDisplay();

        $this->assertStringContainsString(<<<'SDL'
            "Dummy Friend."
            type DummyFriend implements Node {
              id: ID!

              "The id"
              _id: Int!

              "The dummy name"
              name: String!
            }
            SDL, $output);

        $this->assertStringContainsString(<<<'SDL'
            "Cursor connection for DummyFriend."
            type DummyFriendCursorConnection {
              edges: [DummyFriendEdge]
              pageInfo: DummyFriendPageInfo!
              totalCount: Int!
            }
            SDL, $output);

        $this->assertStringContainsString(<<<'SDL'
            "Edge of DummyFriend."
            type DummyFriendEdge {
              node: DummyFriend
              cursor: String!
            }
            SDL, $output);

        $this->assertStringContainsString(<<<'SDL'
            "Information about the current page."
            type DummyFriendPageInfo {
              endCursor: String
              startCursor: String
              hasNextPage: Boolean!
              hasPreviousPage: Boolean!
            }
            SDL, $output);

        $this->assertStringContainsString(<<<'SDL'
              "Updates a DummyFriend."
              updateDummyFriend(input: updateDummyFriendInput!): updateDummyFriendPayload

              "Deletes a DummyFriend."
              deleteDummyFriend(input: deleteDummyFriendInput!): deleteDummyFriendPayload

              "Creates a DummyFriend."
              createDummyFriend(input: createDummyFriendInput!): createDummyFriendPayload
            SDL, $output);

        $this->assertStringContainsString(<<<'SDL'
            "Updates a DummyFriend."
            input updateDummyFriendInput {
              id: ID!

              "The dummy name"
              name: String
              clientMutationId: String
            }
            SDL, $output);

        $this->assertStringContainsString(<<<'SDL'
            "Updates a DummyFriend."
            type updateDummyFriendPayload {
              dummyFriend: DummyFriend
              clientMutationId: String
            }
            SDL, $output);

        $this->assertStringContainsString(<<<'SDL'
            "Deletes a DummyFriend."
            input deleteDummyFriendInput {
              id: ID!
              clientMutationId: String
            }

            "Deletes a DummyFriend."
            type deleteDummyFriendPayload {
              dummyFriend: DummyFriend
              clientMutationId: String
            }
            SDL, $output);

        $this->assertStringContainsString(<<<'SDL'
            "Creates a DummyFriend."
            input createDummyFriendInput {
              "The dummy name"
              name: String!
              clientMutationId: String
            }

            "Creates a DummyFriend."
            type createDummyFriendPayload {
              dummyFriend: DummyFriend
              clientMutationId: String
            }
            SDL, $output);

        $this->assertStringContainsString(<<<'SDL'
            "Updates a OptionalRequiredDummy."
            input updateOptionalRequiredDummyInput {
              id: ID!
              thirdLevel: updateThirdLevelNestedInput
              thirdLevelRequired: updateThirdLevelNestedInput!

              "Get relatedToDummyFriend."
              relatedToDummyFriend: [updateRelatedToDummyFriendNestedInput]
              clientMutationId: String
            }
            SDL, $output);
    }
}
