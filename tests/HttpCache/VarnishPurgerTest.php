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

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class VarnishPurgerTest extends \PHPUnit_Framework_TestCase
{
    public function testPurge()
    {
        $clientProphecy1 = $this->prophesize(ClientInterface::class);
        $clientProphecy1->requestAsync('BAN', '', ['headers' => ['ApiPlatform-Ban-Regex' => '(^|\,)/foo($|\,)']])->willReturn(new Response())->shouldBeCalled();
        $clientProphecy1->requestAsync('BAN', '', ['headers' => ['ApiPlatform-Ban-Regex' => '((^|\,)/foo($|\,))|((^|\,)/bar($|\,))']])->willReturn(new Response())->shouldBeCalled();

        $clientProphecy2 = $this->prophesize(ClientInterface::class);
        $clientProphecy2->requestAsync('BAN', '', ['headers' => ['ApiPlatform-Ban-Regex' => '(^|\,)/foo($|\,)']])->willReturn(new Response())->shouldBeCalled();
        $clientProphecy2->requestAsync('BAN', '', ['headers' => ['ApiPlatform-Ban-Regex' => '((^|\,)/foo($|\,))|((^|\,)/bar($|\,))']])->willReturn(new Response())->shouldBeCalled();

        $purger = new VarnishPurger([$clientProphecy1->reveal(), $clientProphecy2->reveal()]);
        $purger->purge(['/foo']);
        $purger->purge(['/foo', '/bar']);
    }

    public function testEmptyTags()
    {
        $clientProphecy1 = $this->prophesize(ClientInterface::class);
        $clientProphecy1->requestAsync()->shouldNotBeCalled();

        $purger = new VarnishPurger([$clientProphecy1->reveal()]);
        $purger->purge([]);
    }
}
