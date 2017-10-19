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
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class VarnishPurgerTest extends TestCase
{
    public function testPurge()
    {
        $clientProphecy1 = $this->prophesize(ClientInterface::class);
        $clientProphecy1->request('BAN', '', ['headers' => ['ApiPlatform-Ban-Regex' => '(^|\,)/foo($|\,)']])->willReturn(new Response())->shouldBeCalled();
        $clientProphecy1->request('BAN', '', ['headers' => ['ApiPlatform-Ban-Regex' => '((^|\,)/foo($|\,))|((^|\,)/bar($|\,))']])->willReturn(new Response())->shouldBeCalled();

        $clientProphecy2 = $this->prophesize(ClientInterface::class);
        $clientProphecy2->request('BAN', '', ['headers' => ['ApiPlatform-Ban-Regex' => '(^|\,)/foo($|\,)']])->willReturn(new Response())->shouldBeCalled();
        $clientProphecy2->request('BAN', '', ['headers' => ['ApiPlatform-Ban-Regex' => '((^|\,)/foo($|\,))|((^|\,)/bar($|\,))']])->willReturn(new Response())->shouldBeCalled();

        $purger = new VarnishPurger([$clientProphecy1->reveal(), $clientProphecy2->reveal()]);
        $purger->purge(['/foo']);
        $purger->purge(['/foo' => '/foo', '/bar' => '/bar']);
    }

    public function testEmptyTags()
    {
        $clientProphecy1 = $this->prophesize(ClientInterface::class);
        $clientProphecy1->request()->shouldNotBeCalled();

        $purger = new VarnishPurger([$clientProphecy1->reveal()]);
        $purger->purge([]);
    }
}
