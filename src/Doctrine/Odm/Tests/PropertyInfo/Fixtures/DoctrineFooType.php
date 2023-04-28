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

namespace ApiPlatform\Doctrine\Odm\Tests\PropertyInfo\Fixtures;

use Doctrine\ODM\MongoDB\Types\Type;

/**
 * @author Teoh Han Hui <teohhanhui@gmail.com>
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class DoctrineFooType extends Type
{
    /**
     * {@inheritdoc}
     */
    public function convertToDatabaseValue($value): ?string
    {
        if (null === $value) {
            return null;
        }
        if (!$value instanceof Foo) {
            return null;
        }

        return $value->bar;
    }

    /**
     * {@inheritdoc}
     */
    public function convertToPHPValue($value): ?Foo
    {
        if (null === $value) {
            return null;
        }
        if (!\is_string($value)) {
            return null;
        }

        $foo = new Foo();
        $foo->bar = $value;

        return $foo;
    }
}
