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

final class BufferedPurger implements PurgerInterface
{
    private bool $buffering = false;

    /** @var array<string, string> */
    private array $tags = [];

    public function __construct(private readonly PurgerInterface $decorated)
    {
    }

    public function startBuffering(): void
    {
        $this->buffering = true;
    }

    public function purge(array $iris): void
    {
        if (!$this->buffering) {
            $this->decorated->purge($iris);

            return;
        }

        foreach ($iris as $iri) {
            $this->tags[$iri] = $iri;
        }
    }

    public function flush(): void
    {
        $this->buffering = false;

        if (!$this->tags) {
            return;
        }

        $tags = array_values($this->tags);
        $this->tags = [];
        $this->decorated->purge($tags);
    }

    public function getResponseHeaders(array $iris): array
    {
        return $this->decorated->getResponseHeaders($iris);
    }
}
