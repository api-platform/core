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

namespace ApiPlatform\Tests\Fixtures\TestBundle\RuntimeResource;

use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;

final class RuntimeResources
{
    public const NAMESPACE = __NAMESPACE__.'\Generated';

    /** @var array<string, Definition>|null */
    private ?array $definitions = null;
    /** @var array<string, string> */
    private array $classes = [];
    private ?int $generation = null;
    /** @var array<string, true>|null */
    private ?array $staticShortNames = null;
    private bool $loading = false;

    /**
     * @param \Closure(): ResourceNameCollectionFactoryInterface     $staticResourceNames
     * @param \Closure(): ResourceMetadataCollectionFactoryInterface $resourceMetadata
     */
    public function __construct(
        private readonly DefinitionProvider $provider,
        private readonly \Closure $staticResourceNames,
        private readonly \Closure $resourceMetadata,
    ) {
    }

    public static function nameOf(string $class): ?string
    {
        return 1 === preg_match('/^'.preg_quote(self::NAMESPACE, '/').'\\\\([A-Z][a-z0-9]*)_[0-9a-f]{8}\z/', $class, $matches) ? lcfirst($matches[1]) : null;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if ($event->isMainRequest() && $this->generation !== $generation = $this->provider->generation()) {
            $this->generation = $generation;
            $this->definitions = null;
            $this->classes = [];
        }
    }

    /**
     * @return array<string, Definition>
     */
    public function all(): array
    {
        if (null !== $this->definitions || $this->loading) {
            return $this->definitions ?? [];
        }

        $this->loading = true;
        try {
            $this->generation ??= $this->provider->generation();
            $taken = $this->staticShortNames();
            $definitions = [];
            foreach ($this->provider->definitions() as $definition) {
                if (1 === preg_match('/^[a-z][a-z0-9]{0,63}\z/', $definition->name) && !isset($definitions[$definition->name]) && !isset($taken[$definition->name])) {
                    $definitions[$definition->name] = $definition;
                }
            }
        } finally {
            $this->loading = false;
        }

        foreach ($definitions as $name => $definition) {
            $definitions[$name] = new Definition($definition->name, $definition->label, $definition->security, array_filter($definition->attributes, static fn (Attribute $attribute): bool => 1 === preg_match('/^[a-z][a-zA-Z0-9]{0,63}\z/', $attribute->name)
                && !\in_array($attribute->name, ['id', 'resource'], true)
                && (null === $attribute->target() || isset($definitions[$attribute->target()]))));
        }

        $this->classes = [];
        foreach ($definitions as $name => $definition) {
            $reachable = [$name => $definition];
            for ($queue = [$name]; null !== $current = array_shift($queue);) {
                foreach ($definitions[$current]->attributes as $attribute) {
                    if (null !== ($target = $attribute->target()) && !isset($reachable[$target])) {
                        $reachable[$target] = $definitions[$target];
                        $queue[] = $target;
                    }
                }
            }
            ksort($reachable);
            $this->classes[$name] = self::NAMESPACE.'\\'.ucfirst($name).'_'.substr(hash('xxh128', serialize($reachable)), 0, 8);
        }

        return $this->definitions = $definitions;
    }

    /**
     * @return array<string, string>
     */
    public function classes(): array
    {
        $this->all();

        return $this->classes;
    }

    public function classOf(string $name): ?string
    {
        return $this->classes()[$name] ?? null;
    }

    public function get(string $class): ?Definition
    {
        $name = self::nameOf($class);

        return null !== $name && $this->classOf($name) === $class ? $this->definitions[$name] : null;
    }

    /**
     * @return array<string, true>
     */
    private function staticShortNames(): array
    {
        if (null !== $this->staticShortNames) {
            return $this->staticShortNames;
        }

        $shortNames = [];
        foreach (($this->staticResourceNames)()->create() as $class) {
            foreach (($this->resourceMetadata)()->create($class) as $resource) {
                $shortNames[strtolower((string) $resource->getShortName())] = true;
            }
        }

        return $this->staticShortNames = $shortNames;
    }
}
