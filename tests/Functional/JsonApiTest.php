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

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonApiErrorTestResource;
use ApiPlatform\Tests\SetupClassResourcesTrait;

class JsonApiTest extends ApiTestCase
{
    use SetupClassResourcesTrait;
    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [
            JsonApiErrorTestResource::class,
        ];
    }

    public function testError(): void
    {
        self::createClient()->request('GET', '/jsonapi_error_test/nonexistent', [
            'headers' => ['accept' => 'application/vnd.api+json'],
        ]);

        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('content-type', 'application/vnd.api+json; charset=utf-8');
        $this->assertJsonContains([
            'errors' => [
                [
                    // TODO: change this to '400' in 5.x
                    'status' => 400,
                    'detail' => 'Resource "nonexistent" not found.',
                ],
            ],
        ]);
    }
}
