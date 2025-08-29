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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Document;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ApiResource(
    operations: [
        new GetCollection(
            normalizationContext: ['hydra_prefix' => false],
            paginationPartial: true,
            paginationItemsPerPage: 3
        ),
    ]
)]
#[ODM\Document]
class PartialPaginationDummy
{
    #[ODM\Id(strategy: 'INCREMENT')]
    private ?int $id = null;

    #[ODM\Field(type: 'string')]
    private ?string $name = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }
}
