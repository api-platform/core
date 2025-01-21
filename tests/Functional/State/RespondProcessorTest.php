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

namespace ApiPlatform\Tests\Functional\State;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\DummyGetPostDeleteOperation;
use ApiPlatform\Tests\SetupClassResourcesTrait;

class RespondProcessorTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [DummyGetPostDeleteOperation::class];
    }

    public function testAllowHeadersForResourceCollectionReflectsAllowedMethods(): void
    {
        $client = static::createClient();
        $client->request('GET', '/dummy_get_post_delete_operations', [
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);

        $this->assertResponseHeaderSame('allow', 'OPTIONS, HEAD, GET, POST');

        $client = static::createClient();
        $client->request('GET', '/dummy_get_post_delete_operations/1', [
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);

        $this->assertResponseHeaderSame('allow', 'OPTIONS, HEAD, GET, DELETE');
    }

    public function testAcceptPostHeaderForResourceWithPostReflectsAllowedTypes(): void
    {
        $client = static::createClient();
        $client->request('GET', '/dummy_get_post_delete_operations', [
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);

        $this->assertResponseHeaderSame('accept-post', 'application/ld+json, application/hal+json, application/vnd.api+json, application/xml, text/xml, application/json, text/html, application/graphql, multipart/form-data');
    }

    public function testAcceptPostHeaderDoesNotExistResourceWithoutPost(): void
    {
        $client = static::createClient();
        $client->request('GET', '/dummy_get_post_delete_operations/1', [
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);

        $this->assertResponseNotHasHeader('accept-post');
    }
}
