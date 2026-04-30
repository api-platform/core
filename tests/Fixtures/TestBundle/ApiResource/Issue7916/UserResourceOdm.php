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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue7916;

use ApiPlatform\Doctrine\Odm\State\Options;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\Issue7916\User;

/**
 * API Resource DTO for MongoDB User using stateOptions pattern.
 * This is a separate API Resource for the User document which is NOT itself marked #[ApiResource].
 *
 * @see https://github.com/api-platform/core/issues/7916
 */
#[ApiResource(
    stateOptions: new Options(documentClass: User::class),
)]
class UserResourceOdm
{
    public ?int $id = null;
    public ?string $name = null;
    public ?string $email = null;
}
