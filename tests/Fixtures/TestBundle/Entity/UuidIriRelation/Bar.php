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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity\UuidIriRelation;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity()]
#[ApiResource(
    shortName: 'UuidIriRelationBar',
    operations: [
        new Get(
            uriTemplate: '/uuid_iri_relation/bar/{id}',
        ),
    ]
)]
#[ORM\Table(name: 'uuid_iri_relation_bar')]
class Bar
{
    #[ORM\Id]
    #[ORM\Column(type: 'symfony_uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    public Uuid $id;

    #[ORM\Column]
    public string $title = '';

    public function __construct(?Uuid $id = null)
    {
        $this->id = $id ?: Uuid::v7();
    }
}
