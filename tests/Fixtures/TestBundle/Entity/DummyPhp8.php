<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare (strict_types=1);

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use Doctrine\ORM\Mapping as ORM;
#[ApiResource(description: 'Hey PHP 8')]
#[ORM\Entity]
class DummyPhp8
{
    #[ApiProperty(identifier: true, description: 'the identifier')]
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    public $id;
    #[ApiFilter(SearchFilter::class)]
    #[ORM\Column]
    public $filtered;
    #[ApiProperty(description: 'a foo')]
    public function getFoo() : int
    {
        return 0;
    }
}
