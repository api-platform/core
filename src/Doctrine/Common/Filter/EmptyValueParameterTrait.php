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

namespace ApiPlatform\Doctrine\Common\Filter;

use ApiPlatform\Metadata\Parameter;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\Type\CollectionType;
use Symfony\Component\TypeInfo\TypeIdentifier;

/** @internal */
trait EmptyValueParameterTrait
{
    private function ignoreEmptyValue(Parameter $parameter, mixed &$value): bool
    {
        if ('' !== $value && !(\is_array($value) && \in_array('', $value, true))) {
            return false;
        }

        $nativeType = $parameter->getNativeType();
        $isStringLike = static fn (Type $t): bool => $t->isIdentifiedBy(TypeIdentifier::STRING)
            || ($t instanceof CollectionType && $t->getCollectionValueType()->isIdentifiedBy(TypeIdentifier::STRING));

        if (null === $nativeType || $nativeType->isSatisfiedBy($isStringLike)) {
            return false;
        }

        if (\is_array($value)) {
            $value = array_values(array_filter($value, static fn (mixed $v): bool => '' !== $v));

            return [] === $value;
        }

        return true;
    }
}
