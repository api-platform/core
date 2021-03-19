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
class ResourceWithInteger
{
    /**
     * @var int The id
     *
     * @ODM\Id(strategy="INCREMENT", type="int")
     */
    private $id;

    /**
     * @var int
     *
     * @ODM\Field(type="int")
     */
    private $myIntegerField = 0;

    public function getId()
    {
        return $this->id;
    }

    public function getMyIntegerField(): int
    {
        return $this->myIntegerField;
    }

    public function setMyIntegerField(int $myIntegerField): void
    {
        $this->myIntegerField = $myIntegerField;
    }
}
