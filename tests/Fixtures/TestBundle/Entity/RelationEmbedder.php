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
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Relation Embedder.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @ORM\Entity
 */
#[ApiResource(operations: [new Get(), new Put(), new Delete(), new Get(routeName: 'relation_embedded.custom_get'), new Get(uriTemplate: '/api/custom-call/{id}'), new Put(uriTemplate: '/api/custom-call/{id}'), new Post(), new GetCollection()], normalizationContext: ['groups' => ['barcelona']], denormalizationContext: ['groups' => ['chicago']], hydraContext: ['@type' => 'hydra:Operation', 'hydra:title' => 'A custom operation', 'returns' => 'xmls:string'])]
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
