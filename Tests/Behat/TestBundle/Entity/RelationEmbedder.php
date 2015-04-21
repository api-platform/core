<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Tests\Behat\TestBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Relation embedder.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
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
