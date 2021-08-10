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

use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestAssertionsTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\BrowserKit\CookieJar;
use Symfony\Component\HttpFoundation\Cookie as HttpFoundationCookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Copied from Symfony, to remove when https://github.com/symfony/symfony/pull/32207 will be merged.
 */
class WebTestCaseTest extends TestCase
{
    public function testAssertResponseIsSuccessful()
    {
        $this->getResponseTester(new Response())->assertResponseIsSuccessful();
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage("Failed asserting that the Response is successful.\nHTTP/1.0 404 Not Found");
        $this->getResponseTester(new Response('', 404))->assertResponseIsSuccessful();
    }

    public function testAssertResponseStatusCodeSame()
    {
        $this->getResponseTester(new Response())->assertResponseStatusCodeSame(200);
        $this->getResponseTester(new Response('', 404))->assertResponseStatusCodeSame(404);
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage("Failed asserting that the Response status code is 200.\nHTTP/1.0 404 Not Found");
        $this->getResponseTester(new Response('', 404))->assertResponseStatusCodeSame(200);
    }

    public function testAssertResponseRedirects()
    {
        $this->getResponseTester(new Response('', 301))->assertResponseRedirects();
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage("Failed asserting that the Response is redirected.\nHTTP/1.0 200 OK");
        $this->getResponseTester(new Response())->assertResponseRedirects();
    }

    public function testAssertResponseRedirectsWithLocation()
    {
        $this->getResponseTester(new Response('', 301, ['Location' => 'https://example.com/']))->assertResponseRedirects('https://example.com/');
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('is redirected and has header "Location" with value "https://example.com/".');
        $this->getResponseTester(new Response('', 301))->assertResponseRedirects('https://example.com/');
    }

    public function testAssertResponseRedirectsWithStatusCode()
    {
        $this->getResponseTester(new Response('', 302))->assertResponseRedirects(null, 302);
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('is redirected and status code is 301.');
        $this->getResponseTester(new Response('', 302))->assertResponseRedirects(null, 301);
    }

    public function testAssertResponseRedirectsWithLocationAndStatusCode()
    {
        $this->getResponseTester(new Response('', 302, ['Location' => 'https://example.com/']))->assertResponseRedirects('https://example.com/', 302);
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessageMatches('#https://example\.com/#');
        $this->getResponseTester(new Response('', 302))->assertResponseRedirects('https://example.com/', 301);
    }

    public function testAssertResponseHasHeader()
    {
        $this->getResponseTester(new Response())->assertResponseHasHeader('Date');
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Failed asserting that the Response has header "X-Date".');
        $this->getResponseTester(new Response())->assertResponseHasHeader('X-Date');
    }

    public function testAssertResponseNotHasHeader()
    {
        $this->getResponseTester(new Response())->assertResponseNotHasHeader('X-Date');
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Failed asserting that the Response does not have header "Date".');
        $this->getResponseTester(new Response())->assertResponseNotHasHeader('Date');
    }

    public function testAssertResponseHeaderSame()
    {
        $this->getResponseTester(new Response())->assertResponseHeaderSame('Cache-Control', 'no-cache, private');
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Failed asserting that the Response has header "Cache-Control" with value "public".');
        $this->getResponseTester(new Response())->assertResponseHeaderSame('Cache-Control', 'public');
    }

    public function testAssertResponseHeaderNotSame()
    {
        $this->getResponseTester(new Response())->assertResponseHeaderNotSame('Cache-Control', 'public');
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Failed asserting that the Response does not have header "Cache-Control" with value "no-cache, private".');
        $this->getResponseTester(new Response())->assertResponseHeaderNotSame('Cache-Control', 'no-cache, private');
    }

    public function testAssertResponseHasCookie()
    {
        $response = new Response();
        $response->headers->setCookie(HttpFoundationCookie::create('foo', 'bar'));

        $this->getResponseTester($response)->assertResponseHasCookie('foo');
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Failed asserting that the Response has cookie "bar".');
        $this->getResponseTester($response)->assertResponseHasCookie('bar');
    }

    public function testAssertResponseNotHasCookie()
    {
        $response = new Response();
        $response->headers->setCookie(HttpFoundationCookie::create('foo', 'bar'));

        $this->getResponseTester($response)->assertResponseNotHasCookie('bar');
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Failed asserting that the Response does not have cookie "foo".');
        $this->getResponseTester($response)->assertResponseNotHasCookie('foo');
    }

    public function testAssertResponseCookieValueSame()
    {
        $response = new Response();
        $response->headers->setCookie(HttpFoundationCookie::create('foo', 'bar'));

        $this->getResponseTester($response)->assertResponseCookieValueSame('foo', 'bar');
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('has cookie "bar" and has cookie "bar" with value "bar".');
        $this->getResponseTester($response)->assertResponseCookieValueSame('bar', 'bar');
    }

    public function testAssertBrowserHasCookie()
    {
        $this->getClientTester()->assertBrowserHasCookie('foo', '/path');
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Failed asserting that the Browser has cookie "bar".');
        $this->getClientTester()->assertBrowserHasCookie('bar');
    }

    public function testAssertBrowserNotHasCookie()
    {
        $this->getClientTester()->assertBrowserNotHasCookie('bar');
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Failed asserting that the Browser does not have cookie "foo" with path "/path".');
        $this->getClientTester()->assertBrowserNotHasCookie('foo', '/path');
    }

    public function testAssertBrowserCookieValueSame()
    {
        $this->getClientTester()->assertBrowserCookieValueSame('foo', 'bar', false, '/path');
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('has cookie "foo" with path "/path" and has cookie "foo" with path "/path" with value "babar".');
        $this->getClientTester()->assertBrowserCookieValueSame('foo', 'babar', false, '/path');
    }

    public function testAssertRequestAttributeValueSame()
    {
        $this->getRequestTester()->assertRequestAttributeValueSame('foo', 'bar');
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Failed asserting that the Request has attribute "foo" with value "baz".');
        $this->getRequestTester()->assertRequestAttributeValueSame('foo', 'baz');
    }

    public function testAssertRouteSame()
    {
        $this->getRequestTester()->assertRouteSame('homepage', ['foo' => 'bar']);
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Failed asserting that the Request has attribute "_route" with value "articles".');
        $this->getRequestTester()->assertRouteSame('articles');
    }

    private function getResponseTester(Response $response): WebTestCase
    {
        $client = $this->createMock(KernelBrowser::class);
        $client->expects($this->any())->method('getResponse')->willReturn($response);

        return $this->getTester($client);
    }

    private function getClientTester(): WebTestCase
    {
        $client = $this->createMock(KernelBrowser::class);
        $jar = new CookieJar();
        $jar->set(new Cookie('foo', 'bar', null, '/path', 'example.com'));
        $client->expects($this->any())->method('getCookieJar')->willReturn($jar);

        return $this->getTester($client);
    }

    private function getRequestTester(): WebTestCase
    {
        $client = $this->createMock(KernelBrowser::class);
        $request = new Request();
        $request->attributes->set('foo', 'bar');
        $request->attributes->set('_route', 'homepage');
        $client->expects($this->any())->method('getRequest')->willReturn($request);

        return $this->getTester($client);
    }

    private function getTester(KernelBrowser $client): WebTestCase
    {
        return new class($client) extends WebTestCase {
            use WebTestAssertionsTrait;

            public function __construct(KernelBrowser $client)
            {
                parent::__construct();
                self::getClient($client);
            }
        };
    }
}
