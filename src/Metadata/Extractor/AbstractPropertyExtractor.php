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
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
abstract class AbstractPropertyExtractor implements PropertyExtractorInterface
{
    use ResolveValueTrait;

    protected ?array $properties = null;
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
    public function getProperties(): array
    {
        if (null !== $this->properties) {
            return $this->properties;
        }

        $this->properties = [];
        foreach ($this->paths as $path) {
            $this->extractPath($path);
        }

        return $this->properties;
    }

    /**
     * Extracts metadata from a given path.
     */
    abstract protected function extractPath(string $path): void;
}
