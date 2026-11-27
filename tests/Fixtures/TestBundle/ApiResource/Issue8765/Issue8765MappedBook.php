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
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue8765\Issue8765Book;
use Symfony\Component\ObjectMapper\Attribute\Map;
use Symfony\Component\ObjectMapper\Transform\MapCollection;

/**
 * ObjectMapper, associations renamed with #[Map(source: ...)]: writer <- author, comments <- reviews.
 */
#[ApiResource(
    operations: [new GetCollection(uriTemplate: '/issue8765_mapped_books', paginationItemsPerPage: 6)],
    stateOptions: new Options(entityClass: Issue8765Book::class),
)]
#[Map(source: Issue8765Book::class)]
class Issue8765MappedBook
{
    public int $id;

    public string $title;

    #[Map(source: 'author')]
    public Issue8765MappedAuthor $writer;

    /** @var list<Issue8765MappedReview> */
    #[Map(source: 'reviews', transform: new MapCollection())]
    public array $comments = [];
}
