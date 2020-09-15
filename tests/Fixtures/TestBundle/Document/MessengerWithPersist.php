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

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ApiResource(messenger="persist")
 * @ODM\Document
 */
class MessengerWithPersist
{
    /**
     * @var int|null
     *
     * @ApiProperty(identifier=true)
     *
     * @ODM\Id(strategy="INCREMENT", type="integer")
     */
    public $id;

    /**
     * @var string|null
     *
     * @ODM\Field
     */
    public $name;
}
