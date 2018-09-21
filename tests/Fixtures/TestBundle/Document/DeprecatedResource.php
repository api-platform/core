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
 * @ApiResource(deprecationReason="This resource is deprecated")
 * @ODM\Document
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class DeprecatedResource
{
    /**
     * @ODM\Id(strategy="INCREMENT", type="integer")
     */
    public $id;

    /**
     * @var string
     *
     * @ApiProperty(attributes={"deprecation_reason"="This field is deprecated"})
     * @ODM\Field
     */
    public $deprecatedField;
}
