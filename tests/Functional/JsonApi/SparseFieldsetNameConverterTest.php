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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonApi\SparseFieldsetNameConverted;
use ApiPlatform\Tests\SetupClassResourcesTrait;

/**
 * @see https://github.com/api-platform/core/issues/8860
 */
final class SparseFieldsetNameConverterTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [SparseFieldsetNameConverted::class];
    }

    public function testRequestedFieldsGoThroughTheNameConverter(): void
    {
        // The test app's name converter serializes "nameConverted" as "name_converted", so that is what clients send.
        $response = self::createClient()->request(
            'GET',
            '/sparse_fieldset_name_converted/1?fields[SparseFieldsetNameConverted]=title,name_converted',
            ['headers' => ['Accept' => 'application/vnd.api+json']],
        );

        $this->assertResponseIsSuccessful();
        $this->assertSame(
            ['title' => 'Title #1', 'name_converted' => 'Converted #1'],
            $response->toArray()['data']['attributes'],
        );
    }
}
