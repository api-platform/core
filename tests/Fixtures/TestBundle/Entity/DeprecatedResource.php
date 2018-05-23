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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ApiResource(deprecationReason="This resource is deprecated")
 * @ORM\Entity
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class DeprecatedResource
{
    /**
     * @ORM\Id
     * @ORM\Column
     */
    public $id;

    /**
     * @var string
     *
     * @ApiProperty(attributes={"deprecation_reason"="This field is deprecated"})
     * @ORM\Column
     */
    public $deprecatedField;
}
