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

use ApiPlatform\Api\UriVariablesConverterInterface;
use ApiPlatform\Core\Identifier\CompositeIdentifierParser;
use ApiPlatform\Core\Identifier\ContextAwareIdentifierConverterInterface;
use ApiPlatform\Core\Identifier\IdentifierConverterInterface;
use ApiPlatform\Exception\InvalidIdentifierException;
use ApiPlatform\Metadata\HttpOperation;

trait UriVariablesResolverTrait
{
    /** @var ContextAwareIdentifierConverterInterface|IdentifierConverterInterface|UriVariablesConverterInterface|null */
    private $uriVariablesConverter = null;

    /**
     * Resolves an operation's UriVariables to their identifiers values.
     */
    private function getOperationUriVariables(?HttpOperation $operation = null, array $parameters = [], ?string $resourceClass = null): array
    {
        $identifiers = [];

        if (!$operation) {
            return $identifiers;
        }

        foreach ($operation->getUriVariables() ?? [] as $parameterName => $uriVariableDefinition) {
            if (!isset($parameters[$parameterName])) {
                if (!isset($parameters['id'])) {
                    throw new InvalidIdentifierException(sprintf('Parameter "%s" not found, check the identifiers configuration.', $parameterName));
                }

                $parameterName = 'id';
            }

            if (($uriVariableDefinition->getCompositeIdentifier() ?? true) && 1 < ($numIdentifiers = \count($uriVariableDefinition->getIdentifiers() ?? []))) {
                $currentIdentifiers = CompositeIdentifierParser::parse($parameters[$parameterName]);

                if (($foundNumIdentifiers = \count($currentIdentifiers)) !== $numIdentifiers) {
                    throw new InvalidIdentifierException(sprintf('We expected "%s" identifiers and got "%s".', $numIdentifiers, $foundNumIdentifiers));
                }

                foreach ($currentIdentifiers as $key => $value) {
                    $identifiers[$key] = $value;
                }

                continue;
            }

            $identifiers[$parameterName] = $parameters[$parameterName];
        }

        if ($this->uriVariablesConverter) {
            $context = ['operation' => $operation];
            $identifiers = $this->uriVariablesConverter instanceof IdentifierConverterInterface ? $this->uriVariablesConverter->convert($identifiers, $operation->getClass() ?? $resourceClass) : $this->uriVariablesConverter->convert($identifiers, $operation->getClass() ?? $resourceClass, $context);
        }

        return $identifiers;
    }
}
