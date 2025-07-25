<?php

declare(strict_types=1);

namespace ApiPlatform\Hydra;

use Symfony\Component\JsonStreamer\Attribute\StreamedName;

/**
 * @template T
 *
 * @internal
 */
class Collection
{
    #[StreamedName('@context')]
    public string $context = 'VIRTUAL';

    #[StreamedName('@id')]
    public CollectionId $id = CollectionId::VALUE;

    #[StreamedName('@type')]
    public string $type = 'Collection';

    public float $totalItems;

    public ?IriTemplate $search = null;
    public ?PartialCollectionView $view = null;

    /**
     * @var list<T>
     */
    public iterable $member;
}
