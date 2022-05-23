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

namespace ApiPlatform\Core\Tests\Test;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\Client;
use ApiPlatform\Core\Bridge\Symfony\Routing\Router;

class BookTestDeprecatedClient extends ApiTestCase
{
    /** @phpstan-ignore-next-line */
    private Client $client;
    /** @phpstan-ignore-next-line */
    private Router $router;

    protected function setup(): void
    {
        /* @phpstan-ignore-next-line */
        $this->client = static::createClient();
    }

    public function testWorks(): void
    {
        $this->assertTrue(true);
    }
}
