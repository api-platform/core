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
    private $paths;

    public function addPath(string $path, PathItem $pathItem)
    {
        $this->paths[$path] = $pathItem;

        ksort($this->paths);
    }

    public function getPath(string $path): ?PathItem
    {
        return $this->paths[$path] ?? null;
    }

    public function getPaths(): array
    {
        return $this->paths ?? [];
    }
}

if (!class_exists(\ApiPlatform\Core\OpenApi\Model\Paths::class)) {
    class_alias(Paths::class, \ApiPlatform\Core\OpenApi\Model\Paths::class);
}
