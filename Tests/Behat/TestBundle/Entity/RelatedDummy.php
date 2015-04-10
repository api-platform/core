<?php

/*
 * This file is part of the DunglasJsonLdApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\JsonLdApiBundle\Tests\Behat\TestBundle\Entity;

use Dunglas\JsonLdApiBundle\Annotation\Iri;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Related dummy.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @ORM\Entity
 * @Iri("https://schema.org/Product")
 */
class RelatedDummy extends ParentDummy
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;
    /**
     * @ORM\Column
     * @Groups({"barcelona"})
     */
    public $symfony = 'symfony';
    /**
     * @ORM\ManyToOne(targetEntity="UnknownDummy", cascade={"persist"})
     */
    public $unknown;

    public function setUnknown()
    {
        $this->unknown = new UnknownDummy();
    }

    public function getId()
    {
        return $this->id;
    }
}
