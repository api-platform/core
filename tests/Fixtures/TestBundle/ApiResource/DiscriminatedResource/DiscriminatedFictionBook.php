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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\DiscriminatedResource;

use Symfony\Component\Validator\Constraints as Assert;

class DiscriminatedFictionBook extends DiscriminatedBook
{
    public ?string $genre = null;

    #[Assert\Positive]
    public ?int $pageCount = null;

    public ?DiscriminatedBook $sequelOf = null;
}
