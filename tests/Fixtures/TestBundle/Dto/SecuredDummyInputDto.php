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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Dto;

use ApiPlatform\Metadata\ApiProperty;

class SecuredDummyInputDto
{
    /**
     * @var string
     */
    public $title;

    /**
     * @var string
     */
    public $description;

    /**
     * @var string|null
     */
    #[ApiProperty(security: "is_granted('ROLE_ADMIN')")]
    public $adminOnlyProperty;
}
