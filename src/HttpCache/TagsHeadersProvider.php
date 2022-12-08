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

namespace ApiPlatform\HttpCache;

class TagsHeadersProvider implements TagsHeadersProviderInterface
{
    public function __construct(
        private readonly string $headerName,
        private readonly ?string $separator,
    ) {
    }

    public function provideHeaders(array $tags): array
    {
        return [$this->headerName => null === $this->separator ? $tags : implode($this->separator, $tags)];
    }
}
