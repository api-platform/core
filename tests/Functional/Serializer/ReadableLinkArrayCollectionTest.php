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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\ReadableLinkArrayCollection\Api;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\ReadableLinkArrayCollection\Client;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class ReadableLinkArrayCollectionTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [Client::class, Api::class];
    }

    public function testToOneRelationWithReadableLinkFalseRendersIri(): void
    {
        $response = self::createClient()->request('GET', '/readable_link_array_collection_clients/1', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertSame('/readable_link_array_collection_apis/2', $body['singleApi']);
    }

    public function testTypedArrayCollectionWithReadableLinkFalseRendersIris(): void
    {
        $response = self::createClient()->request('GET', '/readable_link_array_collection_clients/1', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertSame(
            ['/readable_link_array_collection_apis/3', '/readable_link_array_collection_apis/4'],
            $body['typedExchangeApis'],
        );
    }

    public function testUntypedArrayCollectionWithReadableLinkFalseTriggersDeprecation(): void
    {
        $deprecations = [];
        set_error_handler(static function (int $errno, string $errstr) use (&$deprecations): bool {
            $deprecations[] = $errstr;

            return true;
        }, \E_USER_DEPRECATED);

        try {
            self::createClient()->request('GET', '/readable_link_array_collection_clients/1', [
                'headers' => ['Accept' => 'application/ld+json'],
            ]);
        } finally {
            restore_error_handler();
        }

        $matched = array_filter($deprecations, static fn (string $m): bool => str_contains($m, 'untypedExchangeApis'));
        $this->assertNotEmpty(
            $matched,
            \sprintf("Expected deprecation about untypedExchangeApis. Got:\n  - %s", implode("\n  - ", $deprecations))
        );
    }
}
