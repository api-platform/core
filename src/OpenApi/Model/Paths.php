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

namespace ApiPlatform\OpenApi\Model;

final class Paths
{
    private array $paths = [];

    public function addPath(string $path, PathItem $pathItem): void
    {
        $this->paths[$path] = $pathItem;
    }

    private function comparePathsByKey($keyA, $keyB): int
    {
        $a = $this->paths[$keyA];
        $b = $this->paths[$keyB];

        $tagsA = [
            ...($a->getGet()?->getTags() ?? []),
            ...($a->getPost()?->getTags() ?? []),
            ...($a->getPut()?->getTags() ?? []),
            ...($a->getDelete()?->getTags() ?? []),
        ];
        sort($tagsA);

        $tagsB = [
            ...($b->getGet()?->getTags() ?? []),
            ...($b->getPost()?->getTags() ?? []),
            ...($b->getPut()?->getTags() ?? []),
            ...($b->getDelete()?->getTags() ?? []),
        ];
        sort($tagsB);

        return match (true) {
            current($tagsA) === current($tagsB) => $keyA <=> $keyB,
            default => current($tagsA) <=> current($tagsB),
        };
    }

    public function getPath(string $path): ?PathItem
    {
        return $this->paths[$path] ?? null;
    }

    public function getPaths(): array
    {
        // sort paths by tags, then by path for each tag
        uksort($this->paths, $this->comparePathsByKey(...));

        return $this->paths;
    }
}
