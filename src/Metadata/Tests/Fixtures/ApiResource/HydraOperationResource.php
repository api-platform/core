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

namespace ApiPlatform\Metadata\Tests\Fixtures\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\HydraOperation;

#[ApiResource]
#[HydraOperation(method: 'DELETE', security: "is_granted('ROLE_ADMIN')")]
#[HydraOperation(method: 'PUT', collection: true, title: 'Bulk replace')]
class HydraOperationResource
{
    public int $id = 0;
}
