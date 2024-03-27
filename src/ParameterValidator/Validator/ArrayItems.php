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

use ApiPlatform\ParameterValidator\ParameterValueExtractor;

final class ArrayItems implements ValidatorInterface
{
    use CheckFilterDeprecationsTrait;

    /**
     * {@inheritdoc}
     */
    public function validate(string $name, array $filterDescription, array $queryParameters): array
    {
        if (!\array_key_exists($name, $queryParameters)) {
            return [];
        }

        $this->checkFilterDeprecations($filterDescription);

        $maxItems = $filterDescription['openapi']['maxItems'] ?? $filterDescription['swagger']['maxItems'] ?? null;
        $minItems = $filterDescription['openapi']['minItems'] ?? $filterDescription['swagger']['minItems'] ?? null;
        $uniqueItems = $filterDescription['openapi']['uniqueItems'] ?? $filterDescription['swagger']['uniqueItems'] ?? false;

        $errorList = [];

        $value = $this->getValue($name, $filterDescription, $queryParameters);
        $nbItems = \count($value);

        if (null !== $maxItems && $nbItems > $maxItems) {
            $errorList[] = sprintf('Query parameter "%s" must contain less than %d values', $name, $maxItems);
        }

        if (null !== $minItems && $nbItems < $minItems) {
            $errorList[] = sprintf('Query parameter "%s" must contain more than %d values', $name, $minItems);
        }

        if (true === $uniqueItems && $nbItems > \count(array_unique($value))) {
            $errorList[] = sprintf('Query parameter "%s" must contain unique values', $name);
        }

        return $errorList;
    }

    private function getValue(string $name, array $filterDescription, array $queryParameters): array
    {
        $value = $queryParameters[$name] ?? null;

        if (empty($value) && '0' !== $value) {
            return [];
        }

        return ParameterValueExtractor::getValue($value, ParameterValueExtractor::getCollectionFormat($filterDescription));
    }
}
