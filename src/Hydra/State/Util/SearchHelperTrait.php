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

namespace ApiPlatform\Hydra\State\Util;

use ApiPlatform\Hydra\IriTemplate;
use ApiPlatform\Hydra\IriTemplateMapping;
use ApiPlatform\Metadata\FilterInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Parameters;
use ApiPlatform\Metadata\QueryParameterInterface;

trait SearchHelperTrait
{
    /**
     * @param FilterInterface[] $filters
     */
    private function getSearch(string $path, ?Operation $operation = null, ?string $resourceClass = null, ?array $filters = [], ?Parameters $parameters = null, ?callable $getFilter = null): IriTemplate
    {
        ['mapping' => $mapping, 'keys' => $keys] = $this->getSearchMappingAndKeys($operation, $resourceClass, $filters, $parameters, $getFilter);

        return new IriTemplate(
            variableRepresentation: 'BasicRepresentation',
            mapping: $mapping,
            template: \sprintf('%s{?%s}', $path, implode(',', $keys)),
        );
    }

    /**
     * @param FilterInterface[] $filters
     *
     * @return array{mapping: list<IriTemplateMapping>, keys: list<string>}
     */
    private function getSearchMappingAndKeys(?Operation $operation = null, ?string $resourceClass = null, ?array $filters = [], ?Parameters $parameters = null, ?callable $getFilter = null): array
    {
        $mapping = [];
        $keys = [];

        if ($filters) {
            foreach ($filters as $filter) {
                foreach ($filter->getDescription($resourceClass) as $variable => $data) {
                    $keys[] = $variable;
                    $mapping[] = new IriTemplateMapping(variable: $variable, property: $data['property'] ?? null, required: $data['required'] ?? false);
                }
            }
        }

        $params = $operation ? ($operation->getParameters() ?? []) : ($parameters ?? []);

        foreach ($params as $key => $parameter) {
            if (!$parameter instanceof QueryParameterInterface || false === $parameter->getHydra()) {
                continue;
            }

            if ($getFilter && ($filterId = $parameter->getFilter()) && \is_string($filterId) && ($filter = $getFilter($filterId))) {
                $filterDescription = $filter->getDescription($resourceClass);

                foreach ($filterDescription as $variable => $description) {
                    // // This is a practice induced by PHP and is not necessary when implementing URI template
                    if (str_ends_with((string) $variable, '[]')) {
                        continue;
                    }

                    if (!($descriptionProperty = $description['property'] ?? null)) {
                        continue;
                    }

                    if (($prop = $parameter->getProperty()) && $descriptionProperty !== $prop) {
                        continue;
                    }

                    $k = str_replace(':property', $description['property'], $key);
                    $variable = str_replace($description['property'], $k, $variable);
                    $keys[] = $variable;
                    $m = new IriTemplateMapping(variable: $variable, property: $description['property'], required: $description['required']);
                    if (null !== ($required = $parameter->getRequired())) {
                        $m->required = $required;
                    }
                    $mapping[] = $m;
                }

                if ($filterDescription) {
                    continue;
                }
            }

            if (str_contains($key, ':property') && $parameter->getProperties()) {
                $required = $parameter->getRequired();
                foreach ($parameter->getProperties() as $prop) {
                    $k = str_replace(':property', $prop, $key);
                    $m = new IriTemplateMapping(variable: $k, property: $prop);
                    $keys[] = $k;
                    if (null !== $required) {
                        $m->required = $required;
                    }
                    $mapping[] = $m;
                }

                continue;
            }

            if (!($property = $parameter->getProperty())) {
                continue;
            }

            $m = new IriTemplateMapping(variable: $key, property: $property);
            $keys[] = $key;
            if (null !== ($required = $parameter->getRequired())) {
                $m->required = $required;
            }
            $mapping[] = $m;
        }

        return ['mapping' => $mapping, 'keys' => $keys];
    }
}
