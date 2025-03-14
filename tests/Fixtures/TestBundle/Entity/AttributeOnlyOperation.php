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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity;

use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Model\Operation;

#[Get(name: 'my own name', openapi: new Operation(operationId: 'my_own_name'))]
final class AttributeOnlyOperation
{
}
