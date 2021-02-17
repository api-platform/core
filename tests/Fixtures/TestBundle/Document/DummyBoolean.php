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
class DummyBoolean
{
    /**
     * @var int
     *
     * @ODM\Id(strategy="INCREMENT", type="int", nullable=true)
     */
    private $id;

    /**
     * @var bool
     *
     * @ODM\Field(type="bool", nullable=true)
     */
    private $isDummyBoolean;

    public function __construct(bool $isDummyBoolean)
    {
        $this->isDummyBoolean = $isDummyBoolean;
    }

    public function getId()
    {
        return $this->id;
    }

    public function isDummyBoolean(): bool
    {
        return $this->isDummyBoolean;
    }
}
