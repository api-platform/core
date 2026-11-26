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

namespace ApiPlatform\State\ObjectMapper;

use ApiPlatform\Metadata\Exception\RuntimeException;
use ApiPlatform\Metadata\SubtypeResolverInterface;
use Symfony\Component\ObjectMapper\Attribute\Map;
use Symfony\Component\ObjectMapper\Exception\MappingException;
use Symfony\Component\ObjectMapper\Metadata\ObjectMapperMetadataFactoryInterface;
use Symfony\Component\ObjectMapper\ObjectMapperAwareInterface;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

trait SubtypeObjectMapperTrait
{
    /** @var array<class-string, bool> */
    private array $abstractCache = [];
    /** @var array<string, array{0: list<array{0: class-string, 1: mixed, 2: ?string}>, 1: list<array{0: class-string, 1: mixed, 2: ?string}>, 2: bool}> */
    private array $candidatesCache = [];
    /** @var array<string, class-string> */
    private array $resolvedCache = [];

    private function resolveTarget(object $source, object|string|null $target): object|string|null
    {
        if (!\is_string($target) || !($this->abstractCache[$target] ??= $this->isAbstract($target))) {
            return $target;
        }

        $key = $source::class.'::'.$target;
        if (isset($this->resolvedCache[$key])) {
            return $this->resolvedCache[$key];
        }

        [$written, $read, $dynamic] = $this->candidatesCache[$key] ??= $this->findCandidates($source, $target);
        $resolved = $this->select($written, $read, $source, $target);
        if (!$dynamic) {
            $this->resolvedCache[$key] = $resolved;
        }

        return $resolved;
    }

    /**
     * @param list<array{0: class-string, 1: mixed, 2: ?string}> $written
     * @param list<array{0: class-string, 1: mixed, 2: ?string}> $read
     *
     * @return class-string
     */
    private function select(array $written, array $read, object $source, string $target): string
    {
        $classes = [];
        foreach ($written as [$class, $if, $mappedSource]) {
            if ($this->passes($if, $source, $class, $mappedSource, $target)) {
                $classes[$class] = true;
            }
        }

        if (1 === \count($classes)) {
            return array_key_first($classes);
        }

        if ($classes) {
            throw new MappingException(\sprintf('Cannot map "%s" to the %s "%s": ambiguous target, candidates are "%s".', $source::class, $this->kind($target), $target, implode('", "', array_keys($classes))));
        }

        $passing = [];
        foreach ($read as $candidate) {
            if ($this->passes($candidate[1], $source, $candidate[0], $candidate[2], $target)) {
                $passing[] = $candidate;
            }
        }

        if (!$passing) {
            throw new MappingException(\sprintf('Cannot map "%s" to the %s "%s": no subclass of "%s" maps from it.', $source::class, $this->kind($target), $target, $target));
        }

        foreach ($passing as [$class, , $mappedSource]) {
            foreach ($passing as [, , $otherSource]) {
                if ($otherSource !== $mappedSource && is_subclass_of($otherSource, $mappedSource)) {
                    continue 2;
                }
            }

            $classes[$class] = true;
        }

        if (\count($classes) > 1) {
            throw new MappingException(\sprintf('Cannot map "%s" to the %s "%s": ambiguous subclasses, candidates are "%s".', $source::class, $this->kind($target), $target, implode('", "', array_keys($classes))));
        }

        return array_key_first($classes);
    }

    /**
     * @return array{0: list<array{0: class-string, 1: mixed, 2: ?string}>, 1: list<array{0: class-string, 1: mixed, 2: ?string}>, 2: bool} candidates from the mappings of the source then from the subclasses, as [concrete class, "if" condition, mapped source class], and whether any condition is dynamic
     */
    private function findCandidates(object $source, string $target): array
    {
        $dynamic = false;
        $written = [];
        foreach ($this->objectMapperMetadata->create($source) as $mapping) {
            if (\is_string($mapping->target) && $this->isInstantiable($mapping->target) && is_a($mapping->target, $target, true)) {
                $written[] = [$mapping->target, $mapping->if, null];
                $dynamic = $dynamic || (null !== $mapping->if && !\is_bool($mapping->if));
            }
        }

        $read = [];
        foreach ($this->subtypeResolver->getSubtypes($target) as $subtype) {
            if (!$this->isInstantiable($subtype)) {
                continue;
            }

            foreach ((new \ReflectionClass($subtype))->getAttributes(Map::class, \ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
                $map = $attribute->newInstance();
                if (\is_string($map->source) && is_a($source, $map->source, true)) {
                    $read[] = [$subtype, $map->if, $map->source];
                    $dynamic = $dynamic || (null !== $map->if && !\is_bool($map->if));
                }
            }
        }

        return [$written, $read, $dynamic];
    }

    private function passes(mixed $if, object $source, string $target, ?string $mappedSource, string $abstract): bool
    {
        if (null === $if || true === $if) {
            return true;
        }

        if (false === $if) {
            return false;
        }

        if (!\is_callable($if)) {
            throw new MappingException(\sprintf('Cannot evaluate the "if" condition of the mapping from "%s" to "%s": service conditions are not supported when mapping to an %s.', $mappedSource ?? $source::class, $target, $this->kind($abstract)));
        }

        return \is_string($if) ? (bool) \call_user_func($if, null) : (bool) $if(null, $source, null);
    }

    private function kind(string $class): string
    {
        return interface_exists($class) ? 'interface' : 'abstract class';
    }

    private function isAbstract(string $class): bool
    {
        return (class_exists($class) || interface_exists($class)) && !(new \ReflectionClass($class))->isInstantiable();
    }

    private function isInstantiable(string $class): bool
    {
        return class_exists($class) && (new \ReflectionClass($class))->isInstantiable();
    }
}

if (false === interface_exists(ObjectMapperAwareInterface::class)) {
    final class SubtypeObjectMapper implements ObjectMapperInterface
    {
        use SubtypeObjectMapperTrait;

        public function __construct(
            private ObjectMapperInterface $decorated,
            private readonly SubtypeResolverInterface $subtypeResolver,
            private readonly ObjectMapperMetadataFactoryInterface $objectMapperMetadata,
        ) {
        }

        public function map(object $source, object|string|null $target = null): object
        {
            return $this->decorated->map($source, $this->resolveTarget($source, $target));
        }
    }
} else {
    final class SubtypeObjectMapper implements ObjectMapperInterface, ObjectMapperAwareInterface
    {
        use SubtypeObjectMapperTrait;

        public function __construct(
            private ObjectMapperInterface $decorated,
            private readonly SubtypeResolverInterface $subtypeResolver,
            private readonly ObjectMapperMetadataFactoryInterface $objectMapperMetadata,
        ) {
            if ($this->decorated instanceof ObjectMapperAwareInterface) {
                $this->decorated = $this->decorated->withObjectMapper($this);
            }
        }

        public function map(object $source, object|string|null $target = null): object
        {
            return $this->decorated->map($source, $this->resolveTarget($source, $target));
        }

        public function withObjectMapper(ObjectMapperInterface $objectMapper): static
        {
            $s = clone $this;

            if (!$s->decorated instanceof ObjectMapperAwareInterface) {
                throw new RuntimeException(\sprintf('Given object mapper "%s" does not implements %s.', get_debug_type($this->decorated), ObjectMapperAwareInterface::class));
            }

            $s->decorated = $s->decorated->withObjectMapper($objectMapper);

            return $s;
        }
    }
}
