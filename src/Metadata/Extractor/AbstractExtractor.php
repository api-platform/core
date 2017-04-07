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

namespace ApiPlatform\Core\Metadata\Extractor;

/**
 * Base file extractor.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
abstract class AbstractExtractor implements ExtractorInterface
{
    protected $paths;
    protected $resources;

    /**
     * @param string[] $paths
     */
    public function __construct(array $paths)
    {
        $this->paths = $paths;
    }

    /**
     * {@inheritdoc}
     */
    public function getResources(): array
    {
        if (null !== $this->resources) {
            return $this->resources;
        }

        $this->resources = [];
        foreach ($this->paths as $path) {
            $this->extractPath($path);
        }

        return $this->resources;
    }

    /**
     * Extracts metadata from a given path.
     *
     * @param string $path
     */
    abstract protected function extractPath(string $path);
}
