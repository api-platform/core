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

namespace ApiPlatform\State;

use ApiPlatform\Metadata\Exception\InvalidIdentifierException;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\UriVariablesConverterInterface;
use ApiPlatform\Metadata\Util\CompositeIdentifierParser;

trait UriVariablesResolverTrait
{
    private ?UriVariablesConverterInterface $uriVariablesConverter = null;

    /**
     * Resolves an operation's UriVariables to their identifiers values.
     */
    private function getOperationUriVariables(?HttpOperation $operation = null, array $parameters = [], ?string $resourceClass = null): array
    {
        $identifiers = [];

        if (!$operation) {
            return $identifiers;
        }

        $uriVariablesMap = [];
        foreach ($operation->getUriVariables() ?? [] as $parameterName => $uriVariableDefinition) {
            if (!isset($parameters[$parameterName])) {
                if (!isset($parameters['id'])) {
                    throw new InvalidIdentifierException(\sprintf('Parameter "%s" not found, check the identifiers configuration.', $parameterName));
                }

                $parameterName = 'id';
            }

            if (($uriVariableDefinition->getCompositeIdentifier() ?? true) && 1 < ($numIdentifiers = \count($uriVariableDefinition->getIdentifiers() ?? []))) {
                $currentIdentifiers = CompositeIdentifierParser::parse($parameters[$parameterName]);

                if (($foundNumIdentifiers = \count($currentIdentifiers)) !== $numIdentifiers) {
                    throw new InvalidIdentifierException(\sprintf('We expected "%s" identifiers and got "%s".', $numIdentifiers, $foundNumIdentifiers));
                }

                foreach ($currentIdentifiers as $key => $value) {
                    $identifiers[$key] = $value;
                    $uriVariablesMap[$key] = $uriVariableDefinition;
                }

                continue;
            }

            $identifiers[$parameterName] = $parameters[$parameterName];
            $uriVariablesMap[$parameterName] = $uriVariableDefinition;
        }

        if ($this->uriVariablesConverter) {
            $context = ['operation' => $operation, 'uri_variables_map' => $uriVariablesMap];
            $identifiers = $this->uriVariablesConverter->convert($identifiers, $operation->getClass() ?? $resourceClass, $context);
        }

        return $identifiers;
    }
}
