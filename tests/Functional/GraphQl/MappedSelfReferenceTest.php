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
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\GraphQlMappedSelfReference\MappedSelfReference;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

/**
 * A GraphQL item query on a self-referencing entity (a mapped ManyToOne pointing
 * back to the same resource class) must apply WHERE id=X only. The relation link
 * has toClass === resourceClass like the identifier-self link, but it must not
 * survive the root-item filter, otherwise handleLinks emits a bogus self-join
 * plus an extra WHERE condition and the item always resolves to null.
 *
 * @see https://github.com/api-platform/core/issues/8305
 */
final class MappedSelfReferenceTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [MappedSelfReference::class];
    }

    public function testItemQueryOnMappedSelfReferenceReturnsTheRecord(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('MappedSelfReference is ORM-only.');
        }

        $this->recreateSchema([MappedSelfReference::class]);

        $manager = $this->getManager();
        $root = new MappedSelfReference();
        $root->name = 'Root';
        $manager->persist($root);
        $child = new MappedSelfReference();
        $child->name = 'Child';
        $child->parent = $root;
        $manager->persist($child);
        $manager->flush();

        // The child's parent is the root, not itself: the bogus self-join
        // (WHERE parent.id = child.id) would never match, so this proves the
        // self-reference link is excluded from the root item lookup.
        $iri = '/mapped_self_references/'.$child->id;

        $response = self::createClient()->request('POST', '/graphql', ['json' => [
            'query' => <<<GRAPHQL
            {
              mappedSelfReference(id: "{$iri}") {
                id
                name
              }
            }
            GRAPHQL,
        ]]);

        $this->assertResponseIsSuccessful();
        $json = $response->toArray(false);
        $this->assertArrayNotHasKey('errors', $json, json_encode($json['errors'] ?? null));
        $this->assertSame('Child', $json['data']['mappedSelfReference']['name']);
    }
}
