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

use ApiPlatform\Metadata\Exception\InvalidArgumentException;
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
    /** @var array<string, true> */
    private array $deprecatedKeys = [];

    /**
     * @param string[] $paths
     */
    public function __construct(protected array $paths, private readonly ?ContainerInterface $container = null, private readonly ?bool $camelCaseKeys = null)
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
     * @param array<string|int, mixed> $config
     *
     * @return array<string|int, mixed>
     */
    protected function normalizeConfigKeys(array $config, string $separator): array
    {
        $normalized = [];
        $origins = [];
        foreach ($config as $key => $value) {
            if (!\is_string($key)) {
                $normalized[$key] = $value;
                continue;
            }

            $camelKey = $this->normalizeConfigKey($key, $separator);
            if (isset($origins[$camelKey])) {
                throw new InvalidArgumentException(\sprintf('"%s" and "%s" are the same configuration key, use only one of them.', $origins[$camelKey], $key));
            }

            $origins[$camelKey] = $key;
            $normalized[$camelKey] = $value;
        }

        return $normalized;
    }

    protected function normalizeConfigKey(string $key, string $separator): string
    {
        if (str_contains($key, $separator)) {
            return lcfirst(str_replace($separator, '', ucwords($key, $separator)));
        }

        if (!preg_match('/[A-Z]/', $key)) {
            return $key;
        }

        $expected = strtolower((string) preg_replace('/(?<!^)[A-Z]/', $separator.'$0', $key));

        if (false === $this->camelCaseKeys) {
            throw new InvalidArgumentException(\sprintf('The camelCase resource configuration key "%s" is not supported when "api_platform.resource_config_camel_case" is false, use "%s" instead.', $key, $expected));
        }

        if (null === $this->camelCaseKeys && !isset($this->deprecatedKeys[$key])) {
            $this->deprecatedKeys[$key] = true;
            trigger_deprecation('api-platform/core', '5.1', 'The camelCase resource configuration key "%s" is deprecated, use "%s" instead. camelCase keys will no longer be supported in 6.0, set "api_platform.resource_config_camel_case" to false to opt in now.', $key, $expected);
        }

        return $key;
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
