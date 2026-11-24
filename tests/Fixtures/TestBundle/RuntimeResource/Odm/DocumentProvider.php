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

namespace ApiPlatform\Tests\Fixtures\TestBundle\RuntimeResource\Odm;

use ApiPlatform\Doctrine\Odm\State\Options;
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\PaginatorInterface;
use ApiPlatform\State\Pagination\TraversablePaginator;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\RuntimeResource\Document;
use ApiPlatform\Tests\Fixtures\TestBundle\RuntimeResource\RuntimeMapper;
use ApiPlatform\Tests\Fixtures\TestBundle\RuntimeResource\RuntimeObject;
use ApiPlatform\Tests\Fixtures\TestBundle\RuntimeResource\RuntimeResources;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\Persistence\ManagerRegistry;
use MongoDB\BSON\UTCDateTime;

final class DocumentProvider implements ProviderInterface
{
    public function __construct(
        private readonly ProviderInterface $itemProvider,
        private readonly ProviderInterface $collectionProvider,
        private readonly ManagerRegistry $registry,
        private readonly RuntimeMapper $mapper,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $name = RuntimeResources::nameOf((string) $operation->getClass());
        if (null === $name) {
            return null;
        }

        $operation = $operation->withStateOptions(new Options(documentClass: Document::class, handleLinks: [self::class, 'handleLinks']));
        if (!$operation instanceof CollectionOperationInterface) {
            $document = $this->itemProvider->provide($operation, $uriVariables, $context);

            return $document instanceof Document ? $this->find($name, (int) $document->getId()) : null;
        }

        $documents = $this->collectionProvider->provide($operation, $uriVariables, $context);
        $objects = $this->mapper->hydrate($name, array_map(static fn (Document $document): int => (int) $document->getId(), iterator_to_array($documents, false)), $this->load(...));

        return $documents instanceof PaginatorInterface ? new TraversablePaginator(new \ArrayIterator($objects), $documents->getCurrentPage(), $documents->getItemsPerPage(), $documents->getTotalItems()) : $objects;
    }

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public static function handleLinks(Builder $aggregationBuilder, array $uriVariables, array $context): void
    {
        $aggregationBuilder->match()->field('resource')->equals(RuntimeResources::nameOf((string) $context['operation']->getClass()));
        if (isset($uriVariables['id'])) {
            $aggregationBuilder->match()->field('id')->equals((int) $uriVariables['id']);
        }
    }

    public function find(string $name, int $id): ?RuntimeObject
    {
        return $this->mapper->hydrate($name, [$id], $this->load(...))[0] ?? null;
    }

    /**
     * @param list<int> $ids
     *
     * @return array<int, array<string, mixed>>
     */
    private function load(string $name, array $ids): array
    {
        $manager = $this->registry->getManagerForClass(Document::class);
        \assert($manager instanceof DocumentManager);

        $values = [];
        foreach ($manager->getDocumentCollection(Document::class)->find(['resource' => $name, '_id' => ['$in' => $ids]], ['typeMap' => ['root' => 'array', 'document' => 'array', 'array' => 'array']]) as $row) {
            $values[$row['_id']] = array_map(static fn (mixed $value): mixed => $value instanceof UTCDateTime ? \DateTimeImmutable::createFromInterface($value->toDateTime()) : $value, $row['values'] ?? []);
        }

        return $values;
    }
}
