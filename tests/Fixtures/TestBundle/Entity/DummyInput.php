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

/**
 * Dummy Input.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @ApiResource
 */
class DummyInput
{
    /**
     * @var int The id
     * @ApiProperty(identifier=true)
     */
    public $id;

    /**
     * @var string The dummy name
     *
     * @ApiProperty
     */
    public $name;
}
