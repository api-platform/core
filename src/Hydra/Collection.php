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
    public string $id = 'VIRTUAL';

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
