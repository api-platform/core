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
use Symfony\Component\TypeInfo\Type\CompositeTypeInterface;
use Symfony\Component\TypeInfo\Type\ObjectType;
use Symfony\Component\TypeInfo\Type\WrappingTypeInterface;

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

    /**
     * https://github.com/symfony/symfony/pull/59845.
     *
     * @return iterable<Type>
     */
    public static function traverse(Type $type, bool $traverseComposite = true, bool $traverseWrapped = true): iterable
    {
        yield $type;

        if ($type instanceof CompositeTypeInterface && $traverseComposite) {
            foreach ($type->getTypes() as $t) {
                yield $t;
            }

            // prevent yielding twice when having a type that is both composite and wrapped
            return;
        }

        if ($type instanceof WrappingTypeInterface && $traverseWrapped) {
            yield $type->getWrappedType();
        }
    }

    /**
     * https://github.com/symfony/symfony/pull/59844.
     *
     * @param callable(Type): bool $specification
     */
    public static function isSatisfiedBy(Type $type, callable $specification): bool
    {
        if ($type instanceof WrappingTypeInterface && $type->wrappedTypeIsSatisfiedBy($specification)) {
            return true;
        }

        if ($type instanceof CompositeTypeInterface && $type->composedTypesAreSatisfiedBy($specification)) {
            return true;
        }

        return $specification($type);
    }

    public static function getCollectionValueType(Type $type): ?Type
    {
        foreach (self::traverse($type) as $t) {
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
        foreach (self::traverse($type) as $t) {
            if ($t instanceof ObjectType) {
                return $t->getClassName();
            }
        }

        return null;
    }
}
