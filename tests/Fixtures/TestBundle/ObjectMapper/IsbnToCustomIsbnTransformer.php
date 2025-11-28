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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ObjectMapper;

use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue7563\BookDto;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Book;
use Symfony\Component\ObjectMapper\TransformCallableInterface;

/**
 * @implements TransformCallableInterface<Book, BookDto>
 */
final readonly class IsbnToCustomIsbnTransformer implements TransformCallableInterface
{
    public function __invoke(mixed $value, object $source, ?object $target): mixed
    {
        return 'custom'.$value;
    }
}
