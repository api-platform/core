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
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    provider: [RelationGroupImpactOnCollection::class, 'getData']
)]
#[GetCollection]
#[Get(normalizationContext: ['groups' => 'related'])]
class RelationGroupImpactOnCollection
{
    public ?int $id;
    #[Groups('related')]
    public ?RelationGroupImpactOnCollectionRelation $related;

    public function __construct($id = null, $related = null)
    {
        $this->id = $id;
        $this->related = $related;
    }

    public static function getData(Operation $operation, array $uriVariables = [], array $context = []): self|array
    {
        $item = new self($uriVariables['id'] ?? 1, new RelationGroupImpactOnCollectionRelation(id: $uriVariables['id'] ?? 1, title: 'foo'));
        if ($operation instanceof CollectionOperationInterface) {
            return [$item];
        }

        return $item;
    }
}
