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

use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\IdentifiersExtractor;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Parameter;
use ApiPlatform\State\ParameterProviderInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

final readonly class IriConverterParameterProvider implements ParameterProviderInterface
{
    public function __construct(
        private IriConverterInterface $iriConverter,
        private PropertyAccessorInterface $propertyAccessor,
        private ?IdentifiersExtractor $identifiersExtractor = null,
    ) {
    }

    public function provide(Parameter $parameter, array $parameters = [], array $context = []): ?Operation
    {
        $operation = $context['operation'] ?? null;
        $value = $parameter->getValue();
        if (!$value) {
            return $operation;
        }

        $id = $this->getIdFromValue($value);
        $parameter->setValue($id);

        return $operation;
    }

    protected function getIdFromValue(string $value): mixed
    {
        try {
            $item = $this->iriConverter->getResourceFromIri($value, ['fetch_data' => false]);

            if (null === $this->identifiersExtractor) {
                return $this->propertyAccessor->getValue($item, 'id');
            }

            $identifiers = $this->identifiersExtractor->getIdentifiersFromItem($item);

            return 1 === \count($identifiers) ? array_pop($identifiers) : $identifiers;
        } catch (InvalidArgumentException) {
            // Do nothing, return the raw value
        }

        return $value;
    }
}
