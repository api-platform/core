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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Relation Embedder.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"barcelona"}},
 *     "denormalization_context"={"groups"={"chicago"}},
 *     "hydra_context"={"@type"="hydra:Operation", "hydra:title"="A custom operation", "returns"="xmls:string"}
 * }, itemOperations={
 *     "get"={"method"="GET"},
 *     "put"={"method"="PUT"},
 *     "custom_get"={"route_name"="relation_embedded.custom_get"},
 *     "custom1"={"path"="/api/custom-call/{id}", "method"="GET"},
 *     "custom2"={"path"="/api/custom-call/{id}", "method"="PUT"},
 * })
 * @ORM\Entity
 */
class RelationEmbedder
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @ORM\Column
     * @Groups({"chicago"})
     */
    public $paris = 'Paris';

    /**
     * @ORM\Column
     * @Groups({"barcelona", "chicago"})
     */
    public $krondstadt = 'Krondstadt';

    /**
     * @ORM\ManyToOne(targetEntity="RelatedDummy", cascade={"persist"})
     * @Groups({"chicago", "barcelona"})
     */
    public $anotherRelated;

    /**
     * @ORM\ManyToOne(targetEntity="RelatedDummy")
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
