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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue7038;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\Operation;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ApiResource(
    operations: [],
    provider: [self::class, 'provide'],
    graphQlOperations: [
        new Query(),
    ],
)]
final class Book
{
    public string $id = '1';
    public string $title = 'Sample Book';
    public Author $author;

    /** @var Collection<int, Category> */
    public Collection $categories;

    public function __construct()
    {
        $this->author = new Author('John Doe');
        $this->categories = new ArrayCollection([
            new Category('Fiction'),
            new Category('Adventure'),
        ]);
    }

    public static function provide(Operation $operation, array $uriVariables = [], array $context = []): self
    {
        return new self();
    }
}
