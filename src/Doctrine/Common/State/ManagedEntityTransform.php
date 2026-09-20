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

namespace ApiPlatform\Doctrine\Common\State;

use ApiPlatform\Metadata\Exception\ExceptionInterface as MetadataExceptionInterface;
use ApiPlatform\Metadata\IdentifiersExtractorInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\State\Util\StateOptionsTrait;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\ObjectMapper\TransformCallableInterface;

/**
 * Resolves a related API Resource to the managed Doctrine object it stands for.
 *
 * The object mapper builds objects and has no identity map. When a resource declares its
 * mapping in the read direction only — `#[Map(source: Entity::class)]` on the resource,
 * which is what keeps the entity free of any presentation concern — a relation typed on
 * another resource is never converted, and lands on the entity as-is:
 *
 *     Expected argument of type "?Author", "AuthorResource" given at property path "author"
 *
 * Declaring the reverse mapping is not enough either: the mapper would then build a fresh
 * entity from the resource's scalars — right identifier, an instance Doctrine has never
 * seen — and the flush raises "A new entity was found through the relationship".
 * Cascading it inserts a duplicate row instead.
 *
 * Nothing has to be declared per relation: the managed class is read from the related
 * resource's state options, and the identifiers from its metadata — never assumed to be
 * called `id`, since a resource keyed on a natural code is just as valid.
 *
 *     #[Map(target: 'author', transform: ManagedEntityTransform::class)]
 *     public ?AuthorResource $author = null;
 *
 * A to-many arrives as an iterable of resources and every item is resolved, which is what
 * `MapCollection` needs on the write side.
 *
 * @implements TransformCallableInterface<object, object>
 *
 * @experimental
 */
final class ManagedEntityTransform implements TransformCallableInterface
{
    use StateOptionsTrait;

    /** @var array<class-string, class-string|false> */
    private array $managedClasses = [];

    public function __construct(
        private readonly ManagerRegistry $managerRegistry,
        private readonly ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory,
        private readonly IdentifiersExtractorInterface $identifiersExtractor,
    ) {
    }

    public function __invoke(mixed $value, object $source, ?object $target): mixed
    {
        if (is_iterable($value)) {
            $resolved = [];

            foreach ($value as $key => $item) {
                $resolved[$key] = $this->resolve($item);
            }

            return $resolved;
        }

        return $this->resolve($value);
    }

    private function resolve(mixed $value): mixed
    {
        if (!\is_object($value) || null === ($class = $this->managedClass($value::class))) {
            return $value;
        }

        if (!$manager = $this->managerRegistry->getManagerForClass($class)) {
            return $value;
        }

        try {
            $identifiers = $this->identifiersExtractor->getIdentifiersFromItem($value);
        } catch (MetadataExceptionInterface) {
            return $value;
        }

        // A resource without a complete identifier stands for no row: hand it back untouched
        // rather than guessing, and let the caller deal with an unresolved relation.
        if (!$identifiers || \count($identifiers) !== \count(array_filter($identifiers, static fn (mixed $identifier): bool => null !== $identifier))) {
            return $value;
        }

        return $manager->find($class, 1 === \count($identifiers) ? current($identifiers) : $identifiers) ?? $value;
    }

    /**
     * @param class-string $resourceClass
     *
     * @return class-string|null
     */
    private function managedClass(string $resourceClass): ?string
    {
        if (isset($this->managedClasses[$resourceClass])) {
            return $this->managedClasses[$resourceClass] ?: null;
        }

        try {
            $resourceMetadataCollection = $this->resourceMetadataCollectionFactory->create($resourceClass);
        } catch (MetadataExceptionInterface) {
            $this->managedClasses[$resourceClass] = false;

            return null;
        }

        foreach ($resourceMetadataCollection as $resourceMetadata) {
            foreach ($resourceMetadata->getOperations() ?? [] as $operation) {
                if ($class = $this->getStateOptionsClass($operation)) {
                    return $this->managedClasses[$resourceClass] = $class;
                }
            }
        }

        $this->managedClasses[$resourceClass] = false;

        return null;
    }
}
