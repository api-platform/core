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

namespace ApiPlatform\Tests\Functional\Issues;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue7349\Foo7349;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class Issue7349Test extends ApiTestCase
{
    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [Foo7349::class];
    }

    /**
     * When using partial pagination, totalItems should not be present.
     *
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function testGetPartialNoItemCount(): void
    {
        $response = self::createClient()->request('GET', '/foo7349s?page=1&itemsPerPage=3&partial=true', [
            'headers' => [
                'Accept' => 'application/ld+json',
            ],
        ]);
        var_dump($response->toArray());
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertArrayNotHasKey('hydra:totalItems', $response->toArray());
    }

    /**
     * When not using partial pagination, totalItems should be present.
     *
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function testGetNoItemCount(): void
    {
        $response = self::createClient()->request('GET', '/foo7349s?page=1&itemsPerPage=3', [
            'headers' => [
                'Accept' => 'application/ld+json',
            ],
        ]);
        var_dump($response->toArray());
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertArrayHasKey('hydra:totalItems', $response->toArray());
    }
}
