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

namespace ApiPlatform\Metadata\Extractor;

use Psr\Container\ContainerInterface;

/**
 * Base file extractor.
 *
 * @author Loïc Frémont <lc.fremont@gmail.com>
 */
abstract class AbstractClosureExtractor implements ClosureExtractorInterface
{
    use ResolveValueTrait;

    protected ?array $closures = null;
    private array $collectedParameters = [];

    /**
     * @param string[] $paths
     */
    public function __construct(protected array $paths, private readonly ?ContainerInterface $container = null)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getClosures(): array
    {
        if (null !== $this->closures) {
            return $this->closures;
        }

        $this->closures = [];
        foreach ($this->paths as $path) {
            $closure = $this->getPHPFileClosure($path)();

            if (!$closure instanceof \Closure || !$this->isClosureSupported($closure)) {
                continue;
            }

            $this->closures[] = $closure;
        }

        return $this->closures;
    }

    /**
     * Check if the closure is supported.
     */
    abstract protected function isClosureSupported(\Closure $closure): bool;

    /**
     * Scope isolated include.
     *
     * Prevents access to $this/self from included files.
     */
    protected function getPHPFileClosure(string $filePath): \Closure
    {
        return \Closure::bind(function () use ($filePath): mixed {
            return require $filePath;
        }, null, null);
    }
}
