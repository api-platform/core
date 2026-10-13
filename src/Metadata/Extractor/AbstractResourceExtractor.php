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

use ApiPlatform\Metadata\Util\ContainerParameterResolver;
use Psr\Container\ContainerInterface;

/**
 * Base file extractor.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
abstract class AbstractResourceExtractor implements ResourceExtractorInterface
{
    protected ?array $resources = null;
    private readonly ContainerParameterResolver $parameterResolver;

    /**
     * @param string[] $paths
     */
    public function __construct(protected array $paths, private readonly ?ContainerInterface $container = null)
    {
        $this->parameterResolver = new ContainerParameterResolver($container);
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
     */
    abstract protected function extractPath(string $path): void;

    /**
     * Recursively replaces %param% placeholders with the service container parameters.
     */
    protected function resolve(mixed $value): mixed
    {
        return $this->parameterResolver->resolve($value);
    }

    /**
     * Resolves a container parameter in an ExpressionLanguage field (security, condition, …) only
     * when the whole trimmed value is a single %param% reference, leaving real expressions (and
     * their modulo "%") untouched.
     */
    protected function resolveExpressionPlaceholder(mixed $value): mixed
    {
        return $this->parameterResolver->resolveExpressionPlaceholder($value);
    }
}
