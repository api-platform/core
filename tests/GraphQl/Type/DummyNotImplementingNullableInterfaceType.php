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

namespace ApiPlatform\Tests\GraphQl\Type;

use GraphQL\Type\Definition\Type;

class DummyNotImplementingNullableInterfaceType extends Type
{
    public function __construct()
    {
        $this->name = 'Dummy';
    }
}
