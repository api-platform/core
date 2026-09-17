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

namespace ApiPlatform\Tests\Functional;

use ApiPlatform\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\HydraCollectionObject\HydraBook;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class HydraCollectionObjectTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [HydraBook::class];
    }

    public function testProviderReturningHydraCollectionObject(): void
    {
        $response = self::createClient()->request('GET', '/hydra_collection_objects', ['headers' => ['Accept' => 'application/ld+json']]);

        $this->assertResponseStatusCodeSame(200);

        $this->assertJsonContains([
            '@context' => '/contexts/HydraBook',
            '@id' => '/hydra_collection_objects',
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => 2,
            'hydra:member' => [
                [
                    '@id' => '/hydra_collection_objects/1',
                    '@type' => 'HydraBook',
                    'title' => 'Hyperion',
                ],
                [
                    '@id' => '/hydra_collection_objects/2',
                    '@type' => 'HydraBook',
                    'title' => 'Endymion',
                ],
            ],
            'hydra:view' => [
                '@id' => '/hydra_collection_objects?page=1',
                '@type' => 'hydra:PartialCollectionView',
                'hydra:first' => '/hydra_collection_objects?page=1',
                'hydra:last' => '/hydra_collection_objects?page=1',
            ],
        ]);

        $this->assertArrayNotHasKey('hydra:search', $response->toArray());
    }
}
