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

use ApiPlatform\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonApi\PageLinksDummy;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class PaginationLinksTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [PageLinksDummy::class];
    }

    public function testLinksKeepTheBracketPageParameter(): void
    {
        $response = self::createClient()->request('GET', '/jsonapi_page_links_dummies?page[page]=2', [
            'headers' => ['Accept' => 'application/vnd.api+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $links = $response->toArray()['links'];
        $this->assertSame('/jsonapi_page_links_dummies?page%5Bpage%5D=2', $links['self']);
        $this->assertSame('/jsonapi_page_links_dummies?page%5Bpage%5D=1', $links['first']);
        $this->assertSame('/jsonapi_page_links_dummies?page%5Bpage%5D=4', $links['last']);
        $this->assertSame('/jsonapi_page_links_dummies?page%5Bpage%5D=1', $links['prev']);
        $this->assertSame('/jsonapi_page_links_dummies?page%5Bpage%5D=3', $links['next']);
    }
}
