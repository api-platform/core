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

namespace ApiPlatform\State\ParameterProvider;

use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Parameter;
use ApiPlatform\State\ParameterNotFound;
use ApiPlatform\State\ParameterProviderInterface;

/**
 * @experimental
 *
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
        if (!($value = $parameter->getValue()) || $value instanceof ParameterNotFound) {
            return $operation;
        }

        $iriConverterContext = ['fetch_data' => $parameter->getExtraProperties()['fetch_data'] ?? false];

        if (\is_array($value)) {
            $entities = [];
            foreach ($value as $v) {
                $entities[] = $this->iriConverter->getResourceFromIri($v, $iriConverterContext);
            }

            $parameter->setValue($entities);

            return $operation;
        }

        $parameter->setValue($this->iriConverter->getResourceFromIri($value, $iriConverterContext));

        return $operation;
    }
}
