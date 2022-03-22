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
use Ramsey\Uuid\Nonstandard\UuidV6;

/**
 * @ApiResource(attributes={
 *     "doctrine_mongodb"={
 *         "execute_options"={
 *             "allowDiskUse"=true
 *         }
 *     },
 *     "filters"={
 *         "my_dummy.mongodb.uuid_range",
 *     }
 * })
 * @ODM\Document
 */
class DummyUuidV6
{
    /**
     * @var string|null The id
     *
     * @ODM\Id(strategy="NONE", type="string", nullable=true)
     */
    private $id;

    public function __construct()
    {
        $this->id = UuidV6::uuid6();
    }

    /**
     * @return string|null
     */
    public function getId()
    {
        return $this->id->toString();
    }
}
