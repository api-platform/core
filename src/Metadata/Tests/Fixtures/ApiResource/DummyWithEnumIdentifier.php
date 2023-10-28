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

class DummyWithEnumIdentifier
{
    public $id;

    public function __construct(
        public StringEnumAsIdentifier $stringEnumAsIdentifier = StringEnumAsIdentifier::FOO,
        public IntEnumAsIdentifier $intEnumAsIdentifier = IntEnumAsIdentifier::FOO,
    ) {
    }
}

enum IntEnumAsIdentifier: int
{
    case FOO = 1;
    case BAR = 2;
}

enum StringEnumAsIdentifier: string
{
    case FOO = 'foo';
    case BAR = 'bar';
}
