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
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Circular Reference.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @ApiResource(attributes={"normalization_context"={"groups"={"circular"}}})
 * @ODM\Document
 */
class CircularReference
{
    /**
     * @ODM\Id(strategy="INCREMENT", type="integer")
     */
    public $id;

    /**
     * @ODM\ReferenceOne(targetDocument=CircularReference::class, inversedBy="children")
     *
     * @Groups({"circular"})
     */
    public $parent;

    /**
     * @ODM\ReferenceMany(targetDocument=CircularReference::class, mappedBy="parent")
     *
     * @Groups({"circular"})
     */
    public $children;

    public function __construct()
    {
        $this->children = new ArrayCollection();
    }
}
