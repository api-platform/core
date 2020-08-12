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

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ApiResource
 * @ORM\Entity
 */
class SlugChildDummy
{
    /**
     * @var int The identifier
     *
     * @ApiProperty(identifier=false)
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string The slug used as API identifier
     *
     * @ApiProperty(identifier=true)
     *
     * @ORM\Column(type="string", length=255, unique=true)
     */
    private $slug;

    /**
     * @ORM\ManyToOne(targetEntity="ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\SlugParentDummy", inversedBy="childDummies")
     * @ORM\JoinColumn(name="parent_dummy_id", referencedColumnName="id")
     *
     * @ApiSubresource
     */
    private $parentDummy;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    public function setSlug(string $slug)
    {
        $this->slug = $slug;
    }

    /**
     * @return SlugParentDummy
     */
    public function getParentDummy()
    {
        return $this->parentDummy;
    }

    public function setParentDummy(?SlugParentDummy $parentDummy = null): self
    {
        $this->parentDummy = $parentDummy;

        return $this;
    }
}
