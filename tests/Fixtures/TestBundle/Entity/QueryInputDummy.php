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
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Query;
use ApiPlatform\Tests\Fixtures\TestBundle\Dto\QueryMethodCriteria;
use Doctrine\ORM\Mapping as ORM;

/**
 * Spike (RFC 10008 QUERY): a resource whose Query operation declares its criteria through an input
 * DTO (QueryMethodCriteria) rather than inline parameters.
 */
#[ApiResource(operations: [
    new Post(),
    new Query(input: QueryMethodCriteria::class),
])]
#[ORM\Entity]
class QueryInputDummy
{
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    #[ORM\Column]
    private string $name = '';

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }
}
