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
 * @ODM\Document
 * @ApiResource(mercure=true)
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class DummyMercure
{
    /**
     * @ODM\Id(strategy="INCREMENT", type="integer")
     */
    public $id;

    /**
     * @ODM\Field(type="string")
     */
    public $name;

    /**
     * @ODM\Field(type="string")
     */
    public $description;

    /**
     * @ODM\ReferenceOne(targetDocument=RelatedDummy::class, storeAs="id", nullable=true)
     */
    public $relatedDummy;
}
