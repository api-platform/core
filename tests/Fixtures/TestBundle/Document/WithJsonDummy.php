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
class WithJsonDummy
{
    /**
     * @var int|null
     *
     * @ODM\Id(strategy="INCREMENT", type="int", nullable=true)
     */
    private $id;

    /**
     * @var array|null
     *
     * @ODM\Field(type="hash", nullable=true)
     */
    public $json;

    public function getId(): ?int
    {
        return $this->id;
    }
}
