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

namespace ApiPlatform\State\Provider;

use ApiPlatform\Doctrine\Orm\Filter\IriFilter;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Parameter;
use ApiPlatform\State\ParameterNotFound;
use ApiPlatform\State\ParameterProviderInterface;

/**
 * @author Vincent Amstoutz
 */
final readonly class IriConverterParameterProvider implements ParameterProviderInterface
{
    public function __construct(
        private IriConverterInterface $iriConverter,
    ) {
    }

    public function provide(Parameter $parameter, array $parameters = [], array $context = []): ?Operation
    {
        $operation = $context['operation'] ?? null;
        $parameterValue = $parameter->getValue();

        $isParameterValueNotSet = !$parameterValue || $parameterValue instanceof ParameterNotFound;
        if (!$parameter->getFilter() instanceof IriFilter || $isParameterValueNotSet) {
            return $operation;
        }

        if (!\is_array($parameterValue)) {
            $parameterValue = [$parameterValue];
        }

        $entities = [];
        foreach ($parameterValue as $iri) {
            $entities[] = $this->iriConverter->getResourceFromIri($iri, ['fetch_data' => false]);
        }

        $parameter->setValue($entities);

        return $operation;
    }
}
