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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Document;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ApiResource
 * @ODM\Document
 */
class ResourceWithFloat
{
    /**
     * @var int The id
     *
     * @ODM\Id(strategy="INCREMENT", type="int")
     */
    private $id;

    /**
     * @var float
     *
     * @ODM\Field(type="float")
     */
    private $myFloatField = 0.0;

    public function getId()
    {
        return $this->id;
    }

    public function getMyFloatField(): float
    {
        return $this->myFloatField;
    }

    public function setMyFloatField(float $myFloatField): void
    {
        $this->myFloatField = $myFloatField;
    }
}
