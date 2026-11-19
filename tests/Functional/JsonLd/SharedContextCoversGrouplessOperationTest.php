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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonLd\GrouplessSiblingOperationResource;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class SharedContextCoversGrouplessOperationTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [GrouplessSiblingOperationResource::class];
    }

    public function testSharedContextHasATermForAPropertyOnlySerializedByTheGrouplessSiblingOperation(): void
    {
        $collection = self::createClient()->request('GET', '/groupless_sibling_operations')->toArray();
        $this->assertArrayHasKey('onlyInGrouplessOperation', $collection['hydra:member'][0]);
        $this->assertSame('groupless-1', $collection['hydra:member'][0]['onlyInGrouplessOperation']);

        $context = self::createClient()->request('GET', '/contexts/GrouplessSiblingOperation')->toArray()['@context'];

        $this->assertArrayHasKey('inItemGroup', $context, 'the grouped item operation must still be covered');
        $this->assertArrayHasKey('onlyInGrouplessOperation', $context, 'a sibling operation with no groups at all serializes every property, so the shared context must not be group-filtered');
    }
}
