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

namespace ApiPlatform\Tests\Functional\JsonApi;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonApi\CircularReference;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class CollectionAttributesTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [CircularReference::class];
    }

    public function testCollectionAttributeSerializesAsRelationshipArray(): void
    {
        $response = self::createClient()->request('GET', '/jsonapi_circular_references/1', [
            'headers' => ['Accept' => 'application/vnd.api+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/vnd.api+json; charset=utf-8');
        $body = $response->toArray();
        $this->assertSame('/jsonapi_circular_references/1', $body['data']['id']);
        $this->assertSame('/jsonapi_circular_references/1', $body['data']['relationships']['parent']['data']['id']);
        $this->assertCount(2, $body['data']['relationships']['children']['data']);
        foreach ($body['data']['relationships']['children']['data'] as $child) {
            $this->assertMatchesRegularExpression('#^/jsonapi_circular_references/(1|2)$#', $child['id']);
        }
    }
}
