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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonLd\AbsolutePagedResource;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class AbsolutePaginationTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [AbsolutePagedResource::class];
    }

    public function testHydraViewUrlsAreAbsolute(): void
    {
        $client = self::createClient([], ['base_uri' => 'http://example.com']);
        $response = $client->request('GET', '/jsonld_absolute_paged?page=3', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertSame([
            '@id' => 'http://example.com/jsonld_absolute_paged?page=3',
            '@type' => 'hydra:PartialCollectionView',
            'hydra:first' => 'http://example.com/jsonld_absolute_paged?page=1',
            'hydra:last' => 'http://example.com/jsonld_absolute_paged?page=10',
            'hydra:previous' => 'http://example.com/jsonld_absolute_paged?page=2',
            'hydra:next' => 'http://example.com/jsonld_absolute_paged?page=4',
        ], $body['hydra:view']);
    }
}
