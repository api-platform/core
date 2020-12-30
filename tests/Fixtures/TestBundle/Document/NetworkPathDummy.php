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
use ApiPlatform\Core\Api\UrlGeneratorInterface;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ApiResource(urlGenerationStrategy=UrlGeneratorInterface::NET_PATH)
 * @ODM\Document
 */
class NetworkPathDummy
{
    /**
     * @ODM\Id(strategy="INCREMENT", type="int")
     */
    private $id;

    /**
     * @ODM\ReferenceOne(targetDocument=NetworkPathRelationDummy::class, inversedBy="networkPathDummies", storeAs="id")
     */
    public $networkPathRelationDummy;

    public function getId()
    {
        return $this->id;
    }
}
