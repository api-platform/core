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

namespace ApiPlatform\Tests;

use Symfony\Component\Config\ConfigCacheInterface;

final class TestSuiteConfigCache implements ConfigCacheInterface
{
    /** @var array<string, string> */
    public static array $hashes = [];

    public function __construct(private readonly ConfigCacheInterface $decorated)
    {
    }

    public function getPath(): string
    {
        return $this->decorated->getPath();
    }

    public function isFresh(): bool
    {
        $p = $this->getPath();
        if (!isset(self::$hashes[$p]) || self::$hashes[$p] !== $this->getHash()) {
            self::$hashes[$p] = $this->getHash();

            return false;
        }

        return $this->decorated->isFresh();
    }

    public function write(string $content, ?array $metadata = null): void
    {
        $this->decorated->write($content, $metadata);
    }

    private function getHash(): string
    {
        return hash_file('xxh3', __DIR__.'/Fixtures/app/var/resources.php');
    }
}
