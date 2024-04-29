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

namespace ApiPlatform\ParameterValidator\Validator;

/**
 * @deprecated use Parameter constraint instead
 */
final class Bounds implements ValidatorInterface
{
    use CheckFilterDeprecationsTrait;

    /**
     * {@inheritdoc}
     */
    public function validate(string $name, array $filterDescription, array $queryParameters): array
    {
        $value = $queryParameters[$name] ?? null;
        if (empty($value) && '0' !== $value) {
            return [];
        }

        $this->checkFilterDeprecations($filterDescription);

        $maximum = $filterDescription['openapi']['maximum'] ?? $filterDescription['swagger']['maximum'] ?? null;
        $minimum = $filterDescription['openapi']['minimum'] ?? $filterDescription['swagger']['minimum'] ?? null;

        $errorList = [];

        if (null !== $maximum) {
            if (($filterDescription['openapi']['exclusiveMaximum'] ?? $filterDescription['swagger']['exclusiveMaximum'] ?? false) && $value >= $maximum) {
                $errorList[] = sprintf('Query parameter "%s" must be less than %s', $name, $maximum);
            } elseif ($value > $maximum) {
                $errorList[] = sprintf('Query parameter "%s" must be less than or equal to %s', $name, $maximum);
            }
        }

        if (null !== $minimum) {
            if (($filterDescription['openapi']['exclusiveMinimum'] ?? $filterDescription['swagger']['exclusiveMinimum'] ?? false) && $value <= $minimum) {
                $errorList[] = sprintf('Query parameter "%s" must be greater than %s', $name, $minimum);
            } elseif ($value < $minimum) {
                $errorList[] = sprintf('Query parameter "%s" must be greater than or equal to %s', $name, $minimum);
            }
        }

        return $errorList;
    }
}
