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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonApi\SparseFieldsetIncludeArticle;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonApi\SparseFieldsetIncludeAuthor;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class SparseFieldsetParameterProviderIncludeTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [SparseFieldsetIncludeArticle::class, SparseFieldsetIncludeAuthor::class];
    }

    public function testIncludedResourceKeepsItsOwnRequestedFields(): void
    {
        $response = self::createClient()->request(
            'GET',
            '/sparse_fieldset_include_articles/1?include=author&fields[SparseFieldsetIncludeAuthor]=name',
            ['headers' => ['Accept' => 'application/vnd.api+json']],
        );

        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertArrayHasKey('included', $body);
        $this->assertSame(['name' => 'Author #1'], $body['included'][0]['attributes']);
    }
}
