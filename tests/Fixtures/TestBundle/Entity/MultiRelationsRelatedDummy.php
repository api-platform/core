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
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Dummy used in different kind of relations in the same resource.
 *
 * @author Thomas Helmrich <thomas@gigabit.de>
 */
#[ApiResource(graphQlOperations: [new QueryCollection(), new Query()])]
#[ORM\Entity]
class MultiRelationsRelatedDummy
{
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    public ?string $name;

    #[ORM\ManyToOne(targetEntity: MultiRelationsDummy::class, inversedBy: 'oneToManyRelations')]
    private ?MultiRelationsDummy $oneToManyRelation = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOneToManyRelation(): ?MultiRelationsDummy
    {
        return $this->oneToManyRelation;
    }

    public function setOneToManyRelation(?MultiRelationsDummy $oneToManyRelation): void
    {
        $this->oneToManyRelation = $oneToManyRelation;
    }
}
