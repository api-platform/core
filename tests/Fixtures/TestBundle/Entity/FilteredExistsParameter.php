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

use ApiPlatform\Doctrine\Orm\Filter\ExistsFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\QueryParameter;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\TypeInfo\Type\BuiltinType;
use Symfony\Component\TypeInfo\TypeIdentifier;

#[ApiResource]
#[GetCollection(
    paginationItemsPerPage: 5,
    parameters: [
        'createdAt' => new QueryParameter(
            filter: new ExistsFilter(),
            nativeType: new BuiltinType(TypeIdentifier::BOOL),
        ),
        'hasCreationDate' => new QueryParameter(
            filter: new ExistsFilter(),
            property: 'createdAt',
            nativeType: new BuiltinType(TypeIdentifier::BOOL),
        ),
        'exists[:property]' => new QueryParameter(
            filter: new ExistsFilter(),
        ),
    ],
)]
#[ORM\Entity]
class FilteredExistsParameter
{
    public function __construct(
        #[ORM\Column]
        #[ORM\Id]
        #[ORM\GeneratedValue(strategy: 'AUTO')]
        public ?int $id = null,

        #[ORM\Column(nullable: true)]
        public ?\DateTimeImmutable $createdAt = null,
    ) {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeImmutable $createdAt): void
    {
        $this->createdAt = $createdAt;
    }
}
