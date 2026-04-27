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

use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue7916\User;

/**
 * API Resource DTO for User using stateOptions pattern.
 * This is a separate API Resource for the User entity which is NOT itself marked #[ApiResource].
 *
 * @see https://github.com/api-platform/core/issues/7916
 */
#[ApiResource(
    stateOptions: new Options(entityClass: User::class),
)]
class UserResource
{
    public ?int $id = null;
    public ?string $name = null;
    public ?string $email = null;
}
