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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Dto;

use ApiPlatform\Tests\Fixtures\TestBundle\Entity\BookStore as BookStoreEntity;
use Symfony\Component\ObjectMapper\Attribute\Map;

#[Map(source: BookStoreEntity::class)]
final class BookStoreCollection
{
    public int $id;

    #[Map(source: 'title')]
    public string $name;

    public string $isbn;
}
