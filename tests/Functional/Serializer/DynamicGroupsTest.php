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

namespace ApiPlatform\Tests\Functional\Serializer;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelationGroupImpactOnCollection;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelationGroupImpactOnCollectionRelation;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class DynamicGroupsTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [RelationGroupImpactOnCollection::class, RelationGroupImpactOnCollectionRelation::class];
    }

    protected function setUp(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped();
        }
    }

    public function testDynamicGroupContextIncludesNestedField(): void
    {
        $response = self::createClient()->request('GET', '/relation_group_impact_on_collections/1', ['headers' => ['Accept' => 'application/ld+json']]);

        $this->assertResponseStatusCodeSame(200);
        $body = $response->toArray();
        $this->assertSame('foo', $body['related']['title']);
    }
}
