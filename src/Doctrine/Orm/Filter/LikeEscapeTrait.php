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

namespace ApiPlatform\Doctrine\Orm\Filter;

/** @internal */
trait LikeEscapeTrait
{
    private const LIKE_ESCAPE_CHAR = '!';

    private function escapeLikeValue(string $value): string
    {
        return preg_replace('/[!%_]/', self::LIKE_ESCAPE_CHAR.'$0', $value);
    }

    private function createLikeExpression(string $field, string $parameterName, bool $caseSensitive): string
    {
        return $caseSensitive
            ? $field.' LIKE :'.$parameterName.' ESCAPE \''.self::LIKE_ESCAPE_CHAR.'\''
            : 'LOWER('.$field.') LIKE LOWER(:'.$parameterName.') ESCAPE \''.self::LIKE_ESCAPE_CHAR.'\'';
    }
}
