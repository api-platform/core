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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\NullableToManyRelation\NullableToManyChild;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\NullableToManyRelation\NullableToManyParent;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class NullableToManyRelationTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [NullableToManyParent::class, NullableToManyChild::class];
    }

    public function testNullableToManyRelationNormalizesAsNull(): void
    {
        $response = self::createClient()->request('GET', '/nullable_to_many_parents/1', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $body = $response->toArray();
        $this->assertArrayHasKey('children', $body);
        $this->assertNull($body['children']);
    }
}
