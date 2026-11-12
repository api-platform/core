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

use ApiPlatform\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonApi\NullableRelationDummy;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class OpenApiLegacySpecVersionTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [NullableRelationDummy::class];
    }

    public function testOpenApi30DocumentOnlyHasOpenApi30RootFields(): void
    {
        $response = self::createClient()->request('GET', '/docs?spec_version=3.0.0', [
            'headers' => ['Accept' => 'application/vnd.openapi+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $document = $response->toArray();
        $this->assertSame('3.0.0', $document['openapi']);

        $allowed = ['openapi', 'info', 'servers', 'paths', 'components', 'security', 'tags', 'externalDocs'];
        $extra = array_filter(array_diff(array_keys($document), $allowed), static fn (string $key): bool => !str_starts_with($key, 'x-'));
        $this->assertSame([], array_values($extra));
    }

    public function testOpenApi30DocumentDoesNotUseTheNullType(): void
    {
        $response = self::createClient()->request('GET', '/docs?spec_version=3.0.0', [
            'headers' => ['Accept' => 'application/vnd.openapi+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $document = $response->toArray();
        $this->assertSame([], self::findNullTypes($document['components']['schemas'] ?? []));

        $relationship = $document['components']['schemas']['JsonApiNullableRelation.jsonapi.resource']['properties']['relationships']['properties']['parent']['properties']['data'] ?? null;
        $this->assertTrue($relationship['nullable'] ?? false);
    }

    /**
     * @return list<string>
     */
    private static function findNullTypes(mixed $node, string $path = ''): array
    {
        if (!\is_array($node)) {
            return [];
        }

        $found = [];
        if (isset($node['type']) && ((\is_array($node['type']) && array_is_list($node['type'])) || 'null' === $node['type'])) {
            $found[] = $path;
        }

        foreach ($node as $key => $child) {
            $found = [...$found, ...self::findNullTypes($child, $path.'/'.$key)];
        }

        return $found;
    }
}
