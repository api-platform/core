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
use ApiPlatform\Core\Annotation\ApiSubresource;
use ApiPlatform\Core\Api\UrlGeneratorInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ApiResource(urlGenerationStrategy=UrlGeneratorInterface::ABS_URL)
 * @ODM\Document
 */
class AbsoluteUrlRelationDummy
{
    /**
     * @ODM\Id(strategy="INCREMENT", type="int")
     */
    private $id;

    /**
     * @ODM\ReferenceMany(targetDocument=AbsoluteUrlDummy::class, mappedBy="absoluteUrlRelationDummy")
     * @ApiSubresource
     */
    public $absoluteUrlDummies;

    public function __construct()
    {
        $this->absoluteUrlDummies = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }
}
