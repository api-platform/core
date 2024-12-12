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
use ApiPlatform\Metadata\Exception\ItemNotFoundException;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Parameter;
use ApiPlatform\State\ParameterProviderInterface;
use Psr\Log\LoggerInterface;

final readonly class IriConverterParameterProvider implements ParameterProviderInterface
{
    public function __construct(
        private IriConverterInterface $iriConverter,
        private LoggerInterface $logger,
    ) {
    }

    public function provide(Parameter $parameter, array $parameters = [], array $context = []): ?Operation
    {
        $operation = $context['operation'] ?? null;
        $value = $parameter->getValue();
        if (!$value) {
            return $operation;
        }

        $iri = $context['request']->getRequestUri() ?? null;
        if (null === $iri) {
            return $operation;
        }

        try {
            $resource = $this->iriConverter->getResourceFromIri($value, $context);

            $operation = $operation->withExtraProperties(array_merge(
                $operation->getExtraProperties() ?? [],
                ['_value' => $resource]
            ));
        } catch (InvalidArgumentException|ItemNotFoundException  $e) {
            $this->logger->error(\sprintf('Invalid IRI "%s": %s', $iri, $e->getMessage()));

            return null;
        }

        return $operation;
    }
}
