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

namespace ApiPlatform\Metadata\Util;

use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\Type\CollectionType;
use Symfony\Component\TypeInfo\Type\ObjectType;

/**
 * @internal
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 */
final class TypeHelper
{
    private function __construct()
    {
    }

    public static function getCollectionValueType(Type $type): ?Type
    {
        foreach ($type->traverse() as $t) {
            if ($t instanceof CollectionType) {
                return $t->getCollectionValueType();
            }
        }

        return null;
    }

    /**
     * @return class-string|null
     */
    public static function getClassName(Type $type): ?string
    {
        foreach ($type->traverse() as $t) {
            if ($t instanceof ObjectType) {
                return $t->getClassName();
            }
        }

        return null;
    }
}
