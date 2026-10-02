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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\ControllerApiOperation\Checkout;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\ControllerApiOperation\CheckoutOutput;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use PHPUnit\Framework\Attributes\DataProvider;

final class ControllerApiOperationTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [CheckoutOutput::class, Checkout::class];
    }

    public function testPostRunsPipeline(): void
    {
        self::createClient()->request('POST', '/controller_api_operation/checkout', [
            'headers' => ['Accept' => 'application/ld+json', 'Content-Type' => 'application/ld+json'],
            'json' => ['reference' => 'abc'],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains([
            '@type' => 'CheckoutOutput',
            'reference' => 'abc',
            'status' => 'confirmed',
        ]);
    }

    public function testValidationRuns(): void
    {
        self::createClient()->request('POST', '/controller_api_operation/checkout', [
            'headers' => ['Accept' => 'application/ld+json', 'Content-Type' => 'application/ld+json'],
            'json' => ['reference' => ''],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    #[DataProvider('itemFormatsProvider')]
    public function testGet(string $accept, string $contentType, callable $assert): void
    {
        $response = self::createClient()->request('GET', '/controller_api_operation/checkouts/1', ['headers' => ['Accept' => $accept]]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', $contentType);
        $assert($this, $response->getContent());
    }

    /**
     * @return iterable<string, array{string, string, callable}>
     */
    public static function itemFormatsProvider(): iterable
    {
        yield 'jsonld' => ['application/ld+json', 'application/ld+json', static function (self $test, string $body): void {
            $data = json_decode($body, true);
            $test->assertSame('Checkout', $data['@type']);
            $test->assertSame('/controller_api_operation/checkouts/1', $data['@id']);
            $test->assertSame('ref-1', $data['reference']);
        }];
        yield 'json' => ['application/json', 'application/json', static function (self $test, string $body): void {
            $test->assertSame(['id' => 1, 'reference' => 'ref-1', 'status' => 'pending'], json_decode($body, true));
        }];
        yield 'csv' => ['text/csv', 'text/csv; charset=utf-8', static function (self $test, string $body): void {
            $test->assertStringContainsString('id,reference,status', $body);
            $test->assertStringContainsString('1,ref-1,pending', $body);
        }];
    }

    public function testGetNotFound(): void
    {
        self::createClient()->request('GET', '/controller_api_operation/checkouts/3', ['headers' => ['Accept' => 'application/ld+json']]);

        $this->assertResponseStatusCodeSame(404);
    }

    #[DataProvider('collectionFormatsProvider')]
    public function testGetCollection(string $accept, string $contentType, callable $assert): void
    {
        $response = self::createClient()->request('GET', '/controller_api_operation/checkouts', ['headers' => ['Accept' => $accept]]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', $contentType);
        $assert($this, $response->getContent());
    }

    /**
     * @return iterable<string, array{string, string, callable}>
     */
    public static function collectionFormatsProvider(): iterable
    {
        yield 'jsonld' => ['application/ld+json', 'application/ld+json', static function (self $test, string $body): void {
            $data = json_decode($body, true);
            $members = $data['hydra:member'] ?? $data['member'];
            $test->assertCount(2, $members);
            $test->assertSame('/controller_api_operation/checkouts/2', $members[1]['@id']);
        }];
        yield 'json' => ['application/json', 'application/json', static function (self $test, string $body): void {
            $data = json_decode($body, true);
            $test->assertCount(2, $data);
            $test->assertSame('ref-2', $data[1]['reference']);
        }];
        yield 'csv' => ['text/csv', 'text/csv; charset=utf-8', static function (self $test, string $body): void {
            $rows = array_values(array_filter(explode("\n", $body)));
            $test->assertCount(3, $rows);
            $test->assertSame('id,reference,status', $rows[0]);
        }];
    }

    #[DataProvider('patchFormatsProvider')]
    public function testPatch(string $accept): void
    {
        self::createClient()->request('PATCH', '/controller_api_operation/checkouts/1', [
            'headers' => ['Accept' => $accept, 'Content-Type' => 'application/merge-patch+json'],
            'json' => ['reference' => 'new'],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonContains(['reference' => 'new', 'status' => 'patched']);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function patchFormatsProvider(): iterable
    {
        yield 'jsonld' => ['application/ld+json'];
        yield 'json' => ['application/json'];
    }

    public function testDelete(): void
    {
        $response = self::createClient()->request('DELETE', '/controller_api_operation/checkouts/1');

        $this->assertResponseStatusCodeSame(204);
        $this->assertSame('', $response->getContent());
    }

    public function testDocumentedInOpenApi(): void
    {
        $response = self::createClient()->request('GET', '/docs.jsonopenapi', ['headers' => ['Accept' => 'application/vnd.openapi+json']]);

        $this->assertResponseIsSuccessful();
        $paths = $response->toArray()['paths'];
        $this->assertArrayHasKey('post', $paths['/controller_api_operation/checkout']);
        $this->assertArrayHasKey('get', $paths['/controller_api_operation/checkouts/{id}']);
        $this->assertArrayHasKey('patch', $paths['/controller_api_operation/checkouts/{id}']);
        $this->assertArrayHasKey('delete', $paths['/controller_api_operation/checkouts/{id}']);
        $this->assertArrayHasKey('get', $paths['/controller_api_operation/checkouts']);
    }
}
