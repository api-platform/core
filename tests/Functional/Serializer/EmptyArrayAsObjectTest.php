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
use ApiPlatform\Tests\Fixtures\TestBundle\Model\EmptyArrayAsObject;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class EmptyArrayAsObjectTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [EmptyArrayAsObject::class];
    }

    public function testGetResourcePreservesEmptyArrayAsObject(): void
    {
        self::createClient()->request('GET', '/empty_array_as_objects/5', ['headers' => ['Accept' => 'application/ld+json']]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonEquals(<<<'JSON'
{
    "@context": "/contexts/EmptyArrayAsObject",
    "@id": "/empty_array_as_objects/6",
    "@type": "EmptyArrayAsObject",
    "id": 6,
    "emptyArray": [],
    "emptyArrayAsObject": {},
    "arrayObjectAsArray": [],
    "arrayObject": {},
    "stringArray": ["foo", "bar"],
    "objectArray": {"foo": 67, "bar": "baz"}
}
JSON);
    }
}
