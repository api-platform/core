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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue5625;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Model\Operation;

/**
 * Currency.
 */
#[ApiResource(operations: [
    new Get(uriTemplate: '/get_security_1', openapi: new Operation(security: [['oauth' => ['CURRENCY_READ']]])),
])]
class Currency
{
    public $id;
    public $name;
}
