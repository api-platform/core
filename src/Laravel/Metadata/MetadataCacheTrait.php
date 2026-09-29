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

namespace ApiPlatform\Laravel\Metadata;

use Illuminate\Support\Facades\Cache;

/**
 * @internal
 */
trait MetadataCacheTrait
{
    private function cached(string $key, callable $create): mixed
    {
        if (isset($this->localCache[$key])) {
            return $this->localCache[$key];
        }

        $store = Cache::store($this->cacheStore);
        if (null !== $metadata = $store->get($key)) {
            return $this->localCache[$key] = $metadata;
        }

        $missingTableReads = $this->modelMetadata?->getMissingTableReads();
        $metadata = $create();

        // built from a missing table: the next call, maybe in another process, has to read it again
        if ($missingTableReads !== $this->modelMetadata?->getMissingTableReads()) {
            return $metadata;
        }

        $store->forever($key, $metadata);

        return $this->localCache[$key] = $metadata;
    }
}
