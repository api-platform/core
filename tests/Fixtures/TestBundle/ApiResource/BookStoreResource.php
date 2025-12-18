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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource;

use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Tests\Fixtures\TestBundle\Dto\BookStoreCollection;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\BookStore as BookStoreEntity;

#[ApiResource(
    stateOptions: new Options(entityClass: BookStoreEntity::class),
    operations: [
        new Get(),
        new GetCollection(
            output: BookStoreCollection::class,
            itemUriTemplate: '_api_/book_store_resources/{id}{._format}_get',
            types: ['BookStoreResource']
        ),
    ],
    normalizationContext: ['hydra_prefix' => false],
)]
final class BookStoreResource
{
    public ?int $id = null;
    public ?string $title = null;
    public ?string $isbn = null;
    public ?string $description = null;
    public ?string $author = null;
}
