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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource;

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Symfony\Validator\Exception\ValidationException;
use Symfony\Component\Validator\ConstraintViolationList;

#[Delete(
    uriTemplate: '/error_with_overriden_status/{id}',
    read: true,
    // To make it work with 3.1, remove in 4
    uriVariables: ['id'],
    provider: [ErrorWithOverridenStatus::class, 'throw'],
    exceptionToStatus: [ValidationException::class => 403]
)]
class ErrorWithOverridenStatus
{
    public static function throw(): void
    {
        throw new ValidationException(new ConstraintViolationList());
    }
}
