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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\MultiRouteBook;
use ApiPlatform\Tests\SetupClassResourcesTrait;

/**
 * Functional test for entrypoint with multiple ApiResource declarations.
 *
 * Tests that when a resource has multiple #[ApiResource] attributes with different
 * URIs but the same shortName, both routes are exposed in the entrypoint with indexed keys.
 */
class MultiRouteEntrypointTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [MultiRouteBook::class];
    }

    /**
     * Test that the entrypoint exposes both routes with indexed keys.
     *
     * When multiple ApiResource declarations exist with the same shortName,
     * they are exposed in the entrypoint with numeric suffixes:
     * - multiRouteBook: /admin/multi_route_books (first declaration)
     * - multiRouteBook_1: /multi_route_books (second declaration, alternate)
     *
     * A warning is logged to guide users toward using distinct shortNames.
     */
    public function testEntrypointExposesMultipleRoutesWithIndexedKeys(): void
    {
        $response = self::createClient()->request('GET', 'index', [
            'headers' => ['accept' => 'application/ld+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();

        // Both routes should be advertised with indexed keys
        $this->assertArrayHasKey('multiRouteBook', $data);
        $this->assertEquals('/admin/multi_route_books', $data['multiRouteBook']);

        $this->assertArrayHasKey('multiRouteBook_1', $data);
        $this->assertEquals('/multi_route_books', $data['multiRouteBook_1']);
    }
}
