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

namespace ApiPlatform\Core\Tests\Bridge\Symfony\Bundle\Test;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\Response;
use PHPUnit\Framework\TestCase;
use Symfony\Component\BrowserKit\Response as BrowserKitResponse;
use Symfony\Component\HttpClient\Exception\ClientException;
use Symfony\Component\HttpClient\Exception\JsonException;
use Symfony\Component\HttpClient\Exception\RedirectionException;
use Symfony\Component\HttpClient\Exception\ServerException;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;

class ResponseTest extends TestCase
{
    public function testCreate(): void
    {
        $browserKitResponse = new BrowserKitResponse('', 200, ['content-type' => 'application/json']);
        $httpFoundationResponse = new HttpFoundationResponse('', 200, ['content-type' => 'application/json']);

        $response = new Response($httpFoundationResponse, $browserKitResponse, []);

        $this->assertSame($httpFoundationResponse, $response->getKernelResponse());
        $this->assertSame($browserKitResponse, $response->getBrowserKitResponse());

        $this->assertSame(200, $response->getInfo('http_code'));
        $this->assertSame(200, $response->getInfo()['http_code']);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('application/json', $response->getHeaders()['content-type'][0]);
        $this->assertSame('', $response->getContent());
    }

    /**
     * @dataProvider errorProvider
     */
    public function testCheckStatus(string $expectedException, int $status): void
    {
        $this->expectException($expectedException);
        $browserKitResponse = new BrowserKitResponse('', $status);
        $httpFoundationResponse = new HttpFoundationResponse('', $status);

        $response = new Response($httpFoundationResponse, $browserKitResponse, []);
        $response->getContent();
    }

    public function errorProvider(): iterable
    {
        yield [ServerException::class, 500];
        yield [ClientException::class, 400];
        yield [RedirectionException::class, 300];
    }

    public function testToArray(): void
    {
        $browserKitResponse = new BrowserKitResponse('{"foo": "bar"}', 200, ['content-type' => 'application/ld+json']);
        $httpFoundationResponse = new HttpFoundationResponse('{"foo": "bar"}', 200, ['content-type' => 'application/ld+json']);

        $response = new Response($httpFoundationResponse, $browserKitResponse, []);
        $this->assertSame(['foo' => 'bar'], $response->toArray());
        $this->assertSame(['foo' => 'bar'], $response->toArray()); // Trigger the cache
    }

    public function testToArrayTransportException(): void
    {
        $this->expectException(TransportException::class);

        $response = new Response(new HttpFoundationResponse(), new BrowserKitResponse(), []);
        $response->toArray();
    }

    public function testInvalidContentType(): void
    {
        $this->expectException(JsonException::class);
        $this->expectExceptionMessage('Response content-type is "application/invalid" while a JSON-compatible one was expected.');

        $browserKitResponse = new BrowserKitResponse('{"foo": "bar"}', 200, ['content-type' => 'application/invalid']);
        $httpFoundationResponse = new HttpFoundationResponse('{"foo": "bar"}', 200, ['content-type' => 'application/invalid']);

        $response = new Response($httpFoundationResponse, $browserKitResponse, []);
        $response->toArray();
    }

    public function testInvalidJson(): void
    {
        $this->expectException(JsonException::class);
        $this->expectExceptionMessage('Control character error, possibly incorrectly encoded');

        $browserKitResponse = new BrowserKitResponse('{"foo}', 200, ['content-type' => 'application/json']);
        $httpFoundationResponse = new HttpFoundationResponse('{"foo}', 200, ['content-type' => 'application/json']);

        $response = new Response($httpFoundationResponse, $browserKitResponse, []);
        $response->toArray();
    }

    public function testNonArrayJson(): void
    {
        $this->expectException(JsonException::class);
        $this->expectExceptionMessage('JSON content was expected to decode to an array, string returned.');

        $browserKitResponse = new BrowserKitResponse('"foo"', 200, ['content-type' => 'application/json']);
        $httpFoundationResponse = new HttpFoundationResponse('"foo"', 200, ['content-type' => 'application/json']);

        $response = new Response($httpFoundationResponse, $browserKitResponse, []);
        $response->toArray();
    }

    public function testCancel(): void
    {
        $response = new Response(new HttpFoundationResponse(), new BrowserKitResponse(), []);
        $response->cancel();

        $this->assertSame('Response has been canceled.', $response->getInfo('error'));
    }
}
