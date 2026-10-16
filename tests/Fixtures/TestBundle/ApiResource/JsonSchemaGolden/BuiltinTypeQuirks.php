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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonSchemaGolden;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use BcMath\Number;

#[ApiResource(
    shortName: 'BuiltinTypeQuirks',
    operations: [
        new Get(uriTemplate: '/builtin_type_quirks/{id}', name: 'builtin_type_quirks_get'),
    ],
)]
final class BuiltinTypeQuirks
{
    public ?int $id = null;

    public mixed $anything = null;

    public array $untyped = [];

    public ?Number $amount = null;
}
