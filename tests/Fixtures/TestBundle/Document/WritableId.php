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
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @ApiResource
 * @ODM\Document
 */
class WritableId
{
    /**
     * @ODM\Id(strategy="UUID", type="string")
     * @Assert\Uuid
     */
    public $id;

    /**
     * @ODM\Field
     */
    public $name;
}
