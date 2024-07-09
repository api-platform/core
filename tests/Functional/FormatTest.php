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

final class FormatTest extends ApiTestCase
{
    public function testShouldReturnHtml(): void
    {
        $r = self::createClient()->request('GET', '/accept_html', ['headers' => ['Accept' => 'text/html']]);
        $this->assertResponseIsSuccessful();
        $this->assertEquals($r->getContent(), '<h1>hello</h1>');
    }
}
