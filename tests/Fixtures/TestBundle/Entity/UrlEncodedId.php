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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use Doctrine\ORM\Mapping as ORM;

/**
 * @author Daniel West <daniel@silverback.is>
 *
 * Resource with an ID that will be URL encoded
 */
#[ApiResource(operations: [new Get(requirements: ['id' => '.+']), new Post(), new GetCollection()])]
#[ORM\Entity]
class UrlEncodedId
{
    #[ORM\Column(type: 'string')]
    #[ORM\Id]
    private string $id = '%encode:id';

    public function getId(): string
    {
        return $this->id;
    }
}
