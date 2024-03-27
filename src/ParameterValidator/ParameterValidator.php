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
use ApiPlatform\ParameterValidator\Validator\ValidatorInterface;
use Psr\Container\ContainerInterface;

/**
 * Validates parameters depending on filter description.
 *
 * @author Julien Deniau <julien.deniau@gmail.com>
 */
class ParameterValidator
{
    use FilterLocatorTrait;

    /** @var list<ValidatorInterface> */
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
                $collectionFormat = ParameterValueExtractor::getCollectionFormat($data);
                $validatorErrors = [];

                // validate simple values
                foreach ($this->validate($name, $data, $queryParameters) as $error) {
                    $validatorErrors[] = $error;
                }

                // manipulate query data to validate each value
                foreach (ParameterValueExtractor::iterateValue($name, $queryParameters, $collectionFormat) as $scalarQueryParameters) {
                    foreach ($this->validate($name, $data, $scalarQueryParameters) as $error) {
                        $validatorErrors[] = $error;
                    }
                }

                if ($validatorErrors) {
                    // Remove duplicate messages
                    $errorList[] = array_unique($validatorErrors);
                }
            }
        }

        if ($errorList) {
            throw new ValidationException(array_merge(...$errorList));
        }
    }

    /** @return iterable<string> validation errors that occured */
    private function validate(string $name, array $data, array $queryParameters): iterable
    {
        foreach ($this->validators as $validator) {
            foreach ($validator->validate($name, $data, $queryParameters) as $error) {
                yield $error;
            }
        }
    }
}
