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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Document;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Relation Embedder.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @ApiResource(
 *     attributes={
 *         "normalization_context"={"groups"={"barcelona"}},
 *         "denormalization_context"={"groups"={"chicago"}},
 *         "hydra_context"={"@type"="hydra:Operation", "hydra:title"="A custom operation", "returns"="xmls:string"}
 *     },
 *     itemOperations={
 *         "get",
 *         "put"={},
 *         "delete",
 *         "custom_get"={"route_name"="relation_embedded.custom_get"},
 *         "custom1"={"path"="/api/custom-call/{id}", "method"="GET"},
 *         "custom2"={"path"="/api/custom-call/{id}", "method"="PUT"},
 *     }
 * )
 * @ODM\Document
 */
class RelationEmbedder
{
    /**
     * @ODM\Id(strategy="INCREMENT", type="integer")
     */
    public $id;

    /**
     * @ODM\Field
     * @Groups({"chicago"})
     */
    public $paris = 'Paris';

    /**
     * @ODM\Field
     * @Groups({"barcelona", "chicago"})
     */
    public $krondstadt = 'Krondstadt';

    /**
     * @ODM\ReferenceOne(targetDocument=RelatedDummy::class, cascade={"persist"})
     * @Groups({"chicago", "barcelona"})
     */
    public $anotherRelated;

    /**
     * @ODM\ReferenceOne(targetDocument=RelatedDummy::class)
     * @Groups({"barcelona", "chicago"})
     */
    protected $related;

    public function getRelated()
    {
        return $this->related;
    }

    public function setRelated(RelatedDummy $relatedDummy)
    {
        $this->related = $relatedDummy;
    }
}
