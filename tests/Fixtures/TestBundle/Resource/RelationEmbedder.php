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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Resource;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Models\RelationEmbedder as RelationEmbedderModel;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Relation Embedder.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @ApiResource(
 *     dataModel=RelationEmbedderModel::class,
 *     attributes={
 *         "normalization_context"={"groups"={"barcelona"}},
 *         "denormalization_context"={"groups"={"chicago"}},
 *         "hydra_context"={"@type"="hydra:Operation", "hydra:title"="A custom operation", "returns"="xmls:string"}
 *     },
 *     itemOperations={
 *         "get",
 *         "put"={},
 *         "delete",
 *         "custom_get"={"route_name"="relation_embedded.custom_get", "method"="GET"},
 *         "custom1"={"path"="/api/custom-call/{id}", "method"="GET"},
 *         "custom2"={"path"="/api/custom-call/{id}", "method"="PUT"},
 *     }
 * )
 */
class RelationEmbedder
{
    /**
     * @ApiProperty(identifier=true)
     */
    public $id;

    /**
     * @Groups({"chicago"})
     */
    public $paris = 'Paris';

    /**
     * @Groups({"barcelona", "chicago"})
     */
    public $krondstadt = 'Krondstadt';

    /**
     * @var ?RelatedDummy
     *
     * @Groups({"chicago", "barcelona"})
     */
    public $anotherRelated;

    /**
     * @Groups({"barcelona", "chicago"})
     */
    protected $related;

    public function getRelated()
    {
        return $this->related;
    }

    public function setRelated(?RelatedDummy $relatedDummy)
    {
        $this->related = $relatedDummy;
    }
}
