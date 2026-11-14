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

namespace ApiPlatform\Serializer\Tests;

use ApiPlatform\Serializer\CacheKeyTrait;
use PHPUnit\Framework\TestCase;

class CacheKeyTraitTest extends TestCase
{
    public function testRequestScopedContextKeysAreIgnored(): void
    {
        $base = ['resource_class' => 'Foo', 'iri' => '/foos/1'];

        $first = $base + ['request_uri' => '/foos?page=1', 'uri' => 'http://localhost/foos?page=1', 'filters' => ['page' => '1']];
        $second = $base + ['request_uri' => '/foos?page=2', 'uri' => 'http://localhost/foos?page=2', 'filters' => ['page' => '2']];

        $this->assertSame($this->key($first), $this->key($second));
        $this->assertNotSame($this->key($first), $this->key(['iri' => '/foos/2'] + $first));
    }

    private function key(array $context): string|bool
    {
        $keyFactory = new class {
            use CacheKeyTrait;

            public const EXCLUDE_FROM_CACHE_KEY = 'exclude_from_cache_key';
            public const OBJECT_TO_POPULATE = 'object_to_populate';
            public const IGNORED_ATTRIBUTES = 'ignored_attributes';

            private array $defaultContext = [self::EXCLUDE_FROM_CACHE_KEY => [], self::IGNORED_ATTRIBUTES => []];

            public function key(array $context): string|bool
            {
                return $this->getCacheKey(null, $context);
            }
        };

        return $keyFactory->key($context);
    }
}
