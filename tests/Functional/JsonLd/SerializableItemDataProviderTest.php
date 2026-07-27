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

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\Model\SerializableResource;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;

final class SerializableItemDataProviderTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [SerializableResource::class];
    }

    #[IgnoreDeprecations]
    public function testGetSerializableResource(): void
    {
        $this->expectUserDeprecationMessage('Since api-platform/core 4.2: The "ApiPlatform\State\SerializerAwareProviderInterface" interface is deprecated and will be removed in 5.0. It violates the dependency injection principle.');

        self::createClient()->request('GET', '/serializable_resources/1');

        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonEquals([
            '@context' => '/contexts/SerializableResource',
            '@id' => '/serializable_resources/1',
            '@type' => 'SerializableResource',
            'id' => 1,
            'foo' => 'Lorem',
            'bar' => 'Ipsum',
        ]);
    }
}
