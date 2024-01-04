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

namespace ApiPlatform\ParameterValidator;

use ApiPlatform\ParameterValidator\Exception\ValidationException;
use ApiPlatform\ParameterValidator\Validator\ArrayItems;
use ApiPlatform\ParameterValidator\Validator\Bounds;
use ApiPlatform\ParameterValidator\Validator\Enum;
use ApiPlatform\ParameterValidator\Validator\Length;
use ApiPlatform\ParameterValidator\Validator\MultipleOf;
use ApiPlatform\ParameterValidator\Validator\Pattern;
use ApiPlatform\ParameterValidator\Validator\Required;
use Psr\Container\ContainerInterface;

/**
 * Validates parameters depending on filter description.
 *
 * @author Julien Deniau <julien.deniau@gmail.com>
 */
class ParameterValidator
{
    use FilterLocatorTrait;

    private array $validators;

    public function __construct(ContainerInterface $filterLocator)
    {
        $this->setFilterLocator($filterLocator);

        $this->validators = [
            new ArrayItems(),
            new Bounds(),
            new Enum(),
            new Length(),
            new MultipleOf(),
            new Pattern(),
            new Required(),
        ];
    }

    public function validateFilters(string $resourceClass, array $resourceFilters, array $queryParameters): void
    {
        $errorList = [];

        foreach ($resourceFilters as $filterId) {
            if (!$filter = $this->getFilter($filterId)) {
                continue;
            }

            foreach ($filter->getDescription($resourceClass) as $name => $data) {
                $collectionFormat = $this->getCollectionFormat($data);
                $validatorErrors = [];

                // validate simple values
                if ($errors = $this->validate($name, $data, $queryParameters)) {
                    $validatorErrors[] = $errors;
                }

                // manipulate query data to validate each value
                foreach ($this->iterateValue($name, $queryParameters, $collectionFormat) as $scalarQueryParameters) {
                    if ($errors = $this->validate($name, $data, $scalarQueryParameters)) {
                        $validatorErrors[] = $errors;
                    }
                }

                if (\count($validatorErrors)) {
                    // Remove duplicate messages
                    $errorList[] = array_unique(array_merge(...$validatorErrors));
                }
            }
        }

        if ($errorList) {
            throw new ValidationException(array_merge(...$errorList));
        }
    }

    /**
     * @param array<string, array<string, mixed>> $filterDescription
     */
    private static function getCollectionFormat(array $filterDescription): string
    {
        return $filterDescription['openapi']['collectionFormat'] ?? $filterDescription['swagger']['collectionFormat'] ?? 'csv';
    }

    /**
     * @param array<string, mixed> $queryParameters
     *
     * @throws \InvalidArgumentException
     */
    private static function iterateValue(string $name, array $queryParameters, string $collectionFormat = 'csv'): \Generator
    {
        foreach ($queryParameters as $key => $value) {
            if ($key === $name || "{$key}[]" === $name) {
                $values = self::getValue($value, $collectionFormat);
                foreach ($values as $v) {
                    yield [$key => $v];
                }
            }
        }
    }

    /**
     * @param int|int[]|string|string[] $value
     *
     * @return int[]|string[]
     */
    private static function getValue(int|string|array $value, string $collectionFormat = 'csv'): array
    {
        if (\is_array($value)) {
            return $value;
        }

        if (\is_string($value)) {
            return explode(self::getSeparator($collectionFormat), $value);
        }

        return [$value];
    }

    /** @return non-empty-string */
    private static function getSeparator(string $collectionFormat): string
    {
        return match ($collectionFormat) {
            'csv' => ',',
            'ssv' => ' ',
            'tsv' => '\t',
            'pipes' => '|',
            default => throw new \InvalidArgumentException(sprintf('Unknown collection format %s', $collectionFormat)),
        };
    }

    private function validate(string $name, array $data, array $queryParameters): array
    {
        $errorList = [];

        foreach ($this->validators as $validator) {
            if ($errors = $validator->validate($name, $data, $queryParameters)) {
                $errorList[] = $errors;
            }
        }

        return array_merge(...$errorList);
    }
}
