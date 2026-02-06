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

use ApiPlatform\Tests\Fixtures\TestBundle\Entity\UniqueBook;
use Symfony\Component\ObjectMapper\Attribute\Map;
use Symfony\Component\Validator\Constraints as Assert;

#[Map(target: UniqueBook::class)]
final class CreateUniqueBookDto
{
    #[Assert\NotBlank]
    #[Assert\Isbn]
    public string $isbn = '';

    #[Assert\NotBlank]
    public string $title = '';
}
