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
use ApiPlatform\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\NestedInputItem;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\NestedInputOwner;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class NestedInputWithoutMutationTest extends ApiTestCase
{
    use GraphQlTestTrait;
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [NestedInputOwner::class, NestedInputItem::class];
    }

    public function testRelatedResourceWithoutMutationIsNotExposedAsMutation(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('ORM only fixture.');
        }

        $response = $this->introspectSchema();

        $this->assertResponseIsSuccessful();
        $mutationFields = [];
        foreach ($response->toArray()['data']['__schema']['types'] as $type) {
            if ('Mutation' === $type['name']) {
                $mutationFields = array_column($type['fields'], 'name');
            }
        }
        $this->assertContains('createNestedInputOwner', $mutationFields);
        $this->assertNotContains('createNestedInputItem', $mutationFields);
    }

    public function testCreateWithNestedInputPersistsRelatedResource(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('ORM only fixture.');
        }

        $this->recreateSchema([NestedInputOwner::class]);

        $response = $this->executeGraphQl(<<<'QUERY'
            mutation {
              createNestedInputOwner(input: {name: "owner", items: [{label: "first"}, {label: "second"}]}) {
                nestedInputOwner { id name }
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $json = $response->toArray(false);
        $this->assertArrayNotHasKey('errors', $json, json_encode($json['errors'] ?? null));
        $this->assertSame('owner', $json['data']['createNestedInputOwner']['nestedInputOwner']['name']);

        $items = $this->getManager()->getRepository(NestedInputItem::class)->findBy([], ['id' => 'ASC']);
        $this->assertSame(['first', 'second'], array_map(static fn (NestedInputItem $i): string => $i->label, $items));
    }
}
