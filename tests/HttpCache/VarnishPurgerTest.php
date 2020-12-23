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

namespace ApiPlatform\Core\Tests\HttpCache;

use ApiPlatform\Core\HttpCache\VarnishPurger;
use ApiPlatform\Core\Tests\ProphecyTrait;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Response;
use LogicException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class VarnishPurgerTest extends TestCase
{
    use ProphecyTrait;

    public function testPurge()
    {
        $clientProphecy1 = $this->prophesize(ClientInterface::class);
        $clientProphecy1->request('BAN', '', ['headers' => ['ApiPlatform-Ban-Regex' => '(^|\,)/foo($|\,)']])->willReturn(new Response())->shouldBeCalled();
        $clientProphecy1->request('BAN', '', ['headers' => ['ApiPlatform-Ban-Regex' => '((^|\,)/foo($|\,))|((^|\,)/bar($|\,))']])->willReturn(new Response())->shouldBeCalled();

        $clientProphecy2 = $this->prophesize(ClientInterface::class);
        $clientProphecy2->request('BAN', '', ['headers' => ['ApiPlatform-Ban-Regex' => '(^|\,)/foo($|\,)']])->willReturn(new Response())->shouldBeCalled();
        $clientProphecy2->request('BAN', '', ['headers' => ['ApiPlatform-Ban-Regex' => '((^|\,)/foo($|\,))|((^|\,)/bar($|\,))']])->willReturn(new Response())->shouldBeCalled();

        $clientProphecy3 = $this->prophesize(ClientInterface::class);
        $clientProphecy3->request('BAN', '', ['headers' => ['ApiPlatform-Ban-Regex' => '(^|\,)/foo($|\,)']])->willReturn(new Response())->shouldBeCalled();
        $clientProphecy3->request('BAN', '', ['headers' => ['ApiPlatform-Ban-Regex' => '(^|\,)/bar($|\,)']])->willReturn(new Response())->shouldBeCalled();

        $clientProphecy4 = $this->prophesize(ClientInterface::class);
        $clientProphecy4->request('BAN', '', ['headers' => ['ApiPlatform-Ban-Regex' => '(^|\,)/foo($|\,)']])->willReturn(new Response())->shouldBeCalled();
        $clientProphecy4->request('BAN', '', ['headers' => ['ApiPlatform-Ban-Regex' => '(^|\,)/bar($|\,)']])->willReturn(new Response())->shouldBeCalled();

        $purger = new VarnishPurger([$clientProphecy1->reveal(), $clientProphecy2->reveal()]);
        $purger->purge(['/foo']);
        $purger->purge(['/foo' => '/foo', '/bar' => '/bar']);

        $purger = new VarnishPurger([$clientProphecy3->reveal(), $clientProphecy4->reveal()], 5);
        $purger->purge(['/foo' => '/foo', '/bar' => '/bar']);
    }

    public function testEmptyTags()
    {
        $clientProphecy1 = $this->prophesize(ClientInterface::class);
        $clientProphecy1->request()->shouldNotBeCalled();

        $purger = new VarnishPurger([$clientProphecy1->reveal()]);
        $purger->purge([]);
    }

    /**
     * @dataProvider provideChunkHeaderCases
     */
    public function testItChunksHeaderToAvoidHittingVarnishLimit(int $maxHeaderLength, array $iris, array $regexesToSend)
    {
        $client = new class() implements ClientInterface {
            public $sentRegexes = [];

            public function send(RequestInterface $request, array $options = []): ResponseInterface
            {
                throw new LogicException('Not implemented');
            }

            public function sendAsync(RequestInterface $request, array $options = []): PromiseInterface
            {
                throw new LogicException('Not implemented');
            }

            public function request($method, $uri, array $options = []): ResponseInterface
            {
                $this->sentRegexes[] = $options['headers']['ApiPlatform-Ban-Regex'];

                return new Response();
            }

            public function requestAsync($method, $uri, array $options = []): PromiseInterface
            {
                throw new LogicException('Not implemented');
            }

            public function getConfig($option = null)
            {
                throw new LogicException('Not implemented');
            }
        };

        $purger = new VarnishPurger([$client], $maxHeaderLength);
        $purger->purge($iris);

        self::assertSame($regexesToSend, $client->sentRegexes);
    }

    public function provideChunkHeaderCases()
    {
        yield 'no iri' => [
            50,
            [],
            [],
        ];

        yield 'one iri' => [
            50,
            ['/foo'],
            ['(^|\,)/foo($|\,)'],
        ];

        yield 'few iris' => [
            50,
            ['/foo', '/bar'],
            ['((^|\,)/foo($|\,))|((^|\,)/bar($|\,))'],
        ];

        yield 'iris to generate a header with exactly the maximum length' => [
            56,
            ['/foo', '/bar', '/baz'],
            ['((^|\,)/foo($|\,))|((^|\,)/bar($|\,))|((^|\,)/baz($|\,))'],
        ];

        yield 'iris to generate a header with exactly the maximum length and a smaller one' => [
            37,
            ['/foo', '/bar', '/baz'],
            [
                '((^|\,)/foo($|\,))|((^|\,)/bar($|\,))',
                '(^|\,)/baz($|\,)',
            ],
        ];

        yield 'with last iri too long to be part of the same header' => [
            50,
            ['/foo', '/bar', '/some-longer-tag'],
            [
                '((^|\,)/foo($|\,))|((^|\,)/bar($|\,))',
                '(^|\,)/some\-longer\-tag($|\,)',
            ],
        ];

        yield 'iris to have five headers' => [
            50,
            ['/foo/1', '/foo/2', '/foo/3', '/foo/4', '/foo/5', '/foo/6', '/foo/7', '/foo/8', '/foo/9', '/foo/10'],
            [
                '((^|\,)/foo/1($|\,))|((^|\,)/foo/2($|\,))',
                '((^|\,)/foo/3($|\,))|((^|\,)/foo/4($|\,))',
                '((^|\,)/foo/5($|\,))|((^|\,)/foo/6($|\,))',
                '((^|\,)/foo/7($|\,))|((^|\,)/foo/8($|\,))',
                '((^|\,)/foo/9($|\,))|((^|\,)/foo/10($|\,))',
            ],
        ];

        yield 'with varnish default limit' => [
            8000,
            array_fill(0, 1000, '/foo'),
            [
                implode('|', array_fill(0, 421, '((^|\,)/foo($|\,))')),
                implode('|', array_fill(0, 421, '((^|\,)/foo($|\,))')),
                implode('|', array_fill(0, 158, '((^|\,)/foo($|\,))')),
            ],
        ];
    }
}
