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
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyEntityWithConstructor;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class ConstructorDeserializationTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [DummyEntityWithConstructor::class];
    }

    protected function setUp(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('ORM-only fixture; `Entity` → `Document` rewrite mangles "DummyEntityWithConstructor".');
        }
        $this->recreateSchema([DummyEntityWithConstructor::class]);
    }

    public function testPostHydratesObjectViaConstructor(): void
    {
        self::createClient()->request(
            'POST',
            '/dummy_entity_with_constructors',
            [
                'headers' => ['Content-Type' => 'application/ld+json', 'Accept' => 'application/ld+json'],
                'body' => json_encode(['foo' => 'hello', 'bar' => 'world', 'items' => [['foo' => 'bar']]]),
            ],
        );

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json');
        $this->assertJsonContains([
            '@context' => '/contexts/DummyEntityWithConstructor',
            '@id' => '/dummy_entity_with_constructors/1',
            '@type' => 'DummyEntityWithConstructor',
            'id' => 1,
            'foo' => 'hello',
            'bar' => 'world',
            'items' => [['@type' => 'DummyObjectWithoutConstructor', 'foo' => 'bar']],
            'baz' => null,
        ]);
    }
}
