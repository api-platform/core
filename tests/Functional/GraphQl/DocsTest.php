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

namespace ApiPlatform\Tests\Functional\GraphQl;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;

final class DocsTest extends ApiTestCase
{
    protected static ?bool $alwaysBootKernel = false;

    public function testRetrieveGraphiQlDocumentation(): void
    {
        self::createClient()->request('GET', '/graphql', ['headers' => ['Accept' => 'text/html']]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'text/html; charset=UTF-8');
    }
}
