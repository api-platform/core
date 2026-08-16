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

namespace ApiPlatform\Meilisearch\Tests\Fixtures;

use ApiPlatform\Meilisearch\Filter\SearchFilter;
use ApiPlatform\Meilisearch\Filter\TermFilter;
use ApiPlatform\Meilisearch\State\CollectionProvider;
use ApiPlatform\Meilisearch\State\ItemProvider;
use ApiPlatform\Meilisearch\State\Options;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;

#[ApiResource(operations: [
    new Get(provider: ItemProvider::class, stateOptions: new Options(index: 'movie')),
    new GetCollection(provider: CollectionProvider::class, stateOptions: new Options(index: 'movie')),
])]
#[ApiFilter(SearchFilter::class, properties: ['title'])]
#[ApiFilter(TermFilter::class, properties: ['genre', 'year'])]
class Movie
{
    #[ApiProperty(identifier: true)]
    public ?int $id = null;

    public ?string $title = null;

    public ?string $genre = null;

    public ?int $year = null;
}
