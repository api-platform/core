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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity\GraphQlTransientSelfReference;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GraphQl\Query;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource(graphQlOperations: [new Query()])]
#[ORM\Entity]
class TransientSelfReference
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    public ?int $id = null;

    #[ORM\Column(type: 'string')]
    public ?string $name = null;

    // Typed as the resource class but NOT a Doctrine association: a transient,
    // runtime-only self reference. LinkFactory builds a relation link from the
    // native type, but the ORM has no association mapping to join on.
    private ?self $relatedButNotMapped = null;

    public function getRelatedButNotMapped(): ?self
    {
        return $this->relatedButNotMapped;
    }

    public function setRelatedButNotMapped(?self $related): void
    {
        $this->relatedButNotMapped = $related;
    }
}
