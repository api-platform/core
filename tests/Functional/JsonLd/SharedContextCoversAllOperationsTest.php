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

namespace ApiPlatform\Tests\Functional\JsonLd;

use ApiPlatform\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonLd\PerOperationGroupsResource;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class SharedContextCoversAllOperationsTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [PerOperationGroupsResource::class];
    }

    public function testSharedContextHasATermForEveryOperationsSerializedProperty(): void
    {
        $collection = self::createClient()->request('GET', '/per_operation_groups')->toArray();
        $this->assertArrayHasKey('summary', $collection['hydra:member'][0]);
        $this->assertSame('summary-1', $collection['hydra:member'][0]['summary']);

        $context = self::createClient()->request('GET', '/contexts/PerOperationGroups')->toArray()['@context'];

        $this->assertArrayHasKey('detail', $context, 'the item operation group must still be covered');
        $this->assertArrayHasKey('summary', $context, 'the collection operation serializes "summary" but the shared context has no term for it: a conforming JSON-LD client drops this field on expansion');
    }
}
