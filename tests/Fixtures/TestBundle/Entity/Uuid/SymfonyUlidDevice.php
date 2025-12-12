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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity\Uuid;

use ApiPlatform\Doctrine\Orm\Filter\UlidFilter;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\QueryParameter;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Ulid;

#[Get]
#[GetCollection(
    parameters: [
        'id' => new QueryParameter(
            filter: new UlidFilter(),
        ),
    ]
)]
#[Post]
#[ORM\Entity]
class SymfonyUlidDevice
{
    #[ORM\Id]
    #[ORM\Column(type: 'ulid', unique: true)]
    public Ulid $id;

    public function __construct(?Ulid $id = null)
    {
        $this->id = $id ?? new Ulid();
    }
}
