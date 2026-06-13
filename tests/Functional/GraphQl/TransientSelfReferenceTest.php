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
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\GraphQlTransientSelfReference\TransientSelfReference;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

/**
 * A GraphQL item query must not crash when the resource has a public property
 * typed as the resource class but not mapped as a Doctrine association. The
 * relation link built from the native type has no association mapping to join
 * on, so the ORM LinksHandlerTrait must skip it instead of throwing
 * "No mapping found for field ... on class ...".
 *
 * @see https://github.com/api-platform/core/issues/8292
 */
final class TransientSelfReferenceTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [TransientSelfReference::class];
    }

    public function testItemQueryWithTransientResourceTypedPropertyDoesNotCrash(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('TransientSelfReference is ORM-only.');
        }

        $this->recreateSchema([TransientSelfReference::class]);

        $manager = $this->getManager();
        $first = new TransientSelfReference();
        $first->name = 'First';
        $manager->persist($first);
        $second = new TransientSelfReference();
        $second->name = 'Second';
        $manager->persist($second);
        $manager->flush();

        // Query the second item: proves the identifier-self link still applies
        // WHERE id=X after the unmapped relation link is skipped.
        $iri = '/transient_self_references/'.$second->id;

        $response = self::createClient()->request('POST', '/graphql', ['json' => [
            'query' => <<<GRAPHQL
            {
              transientSelfReference(id: "{$iri}") {
                id
                name
              }
            }
            GRAPHQL,
        ]]);

        $this->assertResponseIsSuccessful();
        $json = $response->toArray(false);
        $this->assertArrayNotHasKey('errors', $json, json_encode($json['errors'] ?? null));
        $this->assertSame('Second', $json['data']['transientSelfReference']['name']);
    }
}
