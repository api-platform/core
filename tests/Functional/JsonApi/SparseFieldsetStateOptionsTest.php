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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonApi\SparseFieldsetStateOptions;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class SparseFieldsetStateOptionsTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [SparseFieldsetStateOptions::class];
    }

    protected function setUp(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('The fixture maps to a Doctrine ORM entity.');
        }
    }

    public function testComputedResourcePropertyCanBeSelected(): void
    {
        $response = self::createClient()->request(
            'GET',
            '/sparse_fieldset_state_options/1?fields[SparseFieldsetStateOptions]=title,totalTracks',
            ['headers' => ['Accept' => 'application/vnd.api+json']],
        );

        $this->assertResponseIsSuccessful();
        $this->assertSame(
            ['title' => 'Title #1', 'totalTracks' => 12],
            $response->toArray()['data']['attributes'],
        );
    }
}
