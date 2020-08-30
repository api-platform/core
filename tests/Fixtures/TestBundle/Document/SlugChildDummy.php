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

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ApiResource
 * @ODM\Document
 */
class SlugChildDummy
{
    /**
     * @var int The identifier
     *
     * @ApiProperty(identifier=false)
     *
     * @ODM\Id(strategy="INCREMENT", type="integer")
     */
    private $id;

    /**
     * @var string The slug used as API identifier
     *
     * @ApiProperty(identifier=true)
     *
     * @ODM\Field
     */
    private $slug;

    /**
     * @ODM\ReferenceOne(targetDocument=SlugParentDummy::class, inversedBy="childDummies", storeAs="id")
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
