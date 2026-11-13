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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue8765;

use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue8765\Issue8765Book;
use ApiPlatform\Tests\Fixtures\TestBundle\State\Issue8765\Issue8765BookProvider;

/**
 * Custom provider, no serialization groups, nested embeds: author -> country (to-one) and awards (to-many).
 */
#[ApiResource(
    operations: [new GetCollection(uriTemplate: '/issue8765_provider_books_with_nested_author', paginationItemsPerPage: 6)],
    provider: Issue8765BookProvider::class,
    stateOptions: new Options(entityClass: Issue8765Book::class),
)]
final readonly class Issue8765ProviderBookWithNestedAuthor
{
    public function __construct(
        #[ApiProperty(identifier: true)]
        public int $id,
        public string $title,
        #[ApiProperty(readableLink: true)]
        public Issue8765AuthorOutput $author,
    ) {
    }
}
