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

use ApiPlatform\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\QueryParameter;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\TypeInfo\Type\BuiltinType;
use Symfony\Component\TypeInfo\TypeIdentifier;

#[ApiResource]
#[GetCollection(
    parameters: [
        'active' => new QueryParameter(
            filter: new BooleanFilter(),
            nativeType: new BuiltinType(TypeIdentifier::BOOL),
        ),
        'enabled' => new QueryParameter(
            filter: new BooleanFilter(),
            property: 'active',
            nativeType: new BuiltinType(TypeIdentifier::BOOL),
        ),
    ],
)]
#[ORM\Entity]
class FilteredBooleanParameter
{
    public function __construct(
        #[ORM\Column]
        #[ORM\Id]
        #[ORM\GeneratedValue(strategy: 'AUTO')]
        public ?int $id = null,

        #[ORM\Column(nullable: true)]
        public ?bool $active = null,
    ) {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(?bool $isActive): void
    {
        $this->active = $isActive;
    }
}
