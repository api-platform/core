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
use ApiPlatform\Metadata\Patch;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\ManyToOne;

#[ApiResource(
    canonicalUriTemplate: '/canonical_iri_entities/{id}{._format}',
    operations: [
        // declared first, so it is the item operation picked for the IRI of a relation
        new Get(uriTemplate: '/canonical_iri_entities/{id}/summary{._format}', name: 'get_summary'),
        new Get(uriTemplate: '/canonical_iri_entities/{id}{._format}'),
        new Patch(uriTemplate: '/canonical_iri_entities/{id}/rename{._format}', read: true),
        // the operation-level template has precedence over the resource-level one
        new Patch(
            uriTemplate: '/canonical_iri_entities/{id}/override{._format}',
            canonicalUriTemplate: '/canonical_iri_entities/{id}/rename{._format}',
            read: true,
            name: 'patch_override',
        ),
    ],
)]
#[Entity]
class CanonicalIriEntity
{
    #[Id]
    #[Column]
    public ?int $id = null;

    #[Column]
    public string $name = '';

    #[ManyToOne]
    public ?CanonicalIriEntity $parent = null;
}
