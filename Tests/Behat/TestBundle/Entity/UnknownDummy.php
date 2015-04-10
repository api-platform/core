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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Dunglas\JsonLdApiBundle\Annotation\Iri;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * UnknownDummy.
 *
 * @ORM\Entity
 */
class UnknownDummy
{
    /**
     * @var int The id.
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    public function getId()
    {
        return $this->id;
    }
}
