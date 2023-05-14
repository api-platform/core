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

namespace ApiPlatform\Api\QueryParameterValidator\Validator;

final class Enum implements ValidatorInterface
{
    use CheckFilterDeprecationsTrait;

    /**
     * {@inheritdoc}
     */
    public function validate(string $name, array $filterDescription, array $queryParameters): array
    {
        $value = $queryParameters[$name] ?? null;
        if (empty($value) && '0' !== $value || !\is_string($value)) {
            return [];
        }

        $this->checkFilterDeprecations($filterDescription);

        $enum = $filterDescription['openapi']['enum'] ?? $filterDescription['swagger']['enum'] ?? null;

        if (null !== $enum && !\in_array($value, $enum, true)) {
            return [
                sprintf('Query parameter "%s" must be one of "%s"', $name, implode(', ', $enum)),
            ];
        }

        return [];
    }
}
