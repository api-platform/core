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
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ApiResource
 * @ODM\Document
 */
class ResourceWithBoolean
{
    /**
     * @var int The id
     *
     * @ODM\Id(strategy="INCREMENT", type="int")
     */
    private $id;

    /**
     * @var bool
     *
     * @ODM\Field(type="bool")
     */
    private $myBooleanField = false;

    public function getId()
    {
        return $this->id;
    }

    public function getMyBooleanField(): bool
    {
        return $this->myBooleanField;
    }

    public function setMyBooleanField(bool $myBooleanField): void
    {
        $this->myBooleanField = $myBooleanField;
    }
}
