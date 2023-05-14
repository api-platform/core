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

namespace ApiPlatform\Api\QueryParameterValidator;

use ApiPlatform\Api\FilterLocatorTrait;
use ApiPlatform\Api\QueryParameterValidator\Validator\ArrayItems;
use ApiPlatform\Api\QueryParameterValidator\Validator\Bounds;
use ApiPlatform\Api\QueryParameterValidator\Validator\Enum;
use ApiPlatform\Api\QueryParameterValidator\Validator\Length;
use ApiPlatform\Api\QueryParameterValidator\Validator\MultipleOf;
use ApiPlatform\Api\QueryParameterValidator\Validator\Pattern;
use ApiPlatform\Api\QueryParameterValidator\Validator\Required;
use ApiPlatform\Exception\FilterValidationException;
use Psr\Container\ContainerInterface;

/**
 * Validates query parameters depending on filter description.
 *
 * @author Julien Deniau <julien.deniau@gmail.com>
 */
class QueryParameterValidator
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
                foreach ($this->validators as $validator) {
                    if ($errors = $validator->validate($name, $data, $queryParameters)) {
                        $errorList[] = $errors;
                    }
                }
            }
        }

        if ($errorList) {
            throw new FilterValidationException(array_merge(...$errorList));
        }
    }
}
