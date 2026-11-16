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

use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\Exception\ItemNotFoundException;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Parameter;
use ApiPlatform\State\ParameterNotFound;
use ApiPlatform\State\ParameterProviderInterface;
use Psr\Log\LoggerInterface;

/**
 * @author Vincent Amstoutz <vincent.amstoutz.dev@gmail.com>
 */
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
        if (null === $value || $value instanceof ParameterNotFound) {
            return $operation;
        }

        $extraProperties = $parameter->getExtraProperties();
        $iriConverterContext = [
            'fetch_data' => $extraProperties['fetch_data'] ?? false,
            'map_resource' => $extraProperties['map_resource'] ?? (null === $parameter->getFilter()),
        ];

        $resources = [];
        foreach (\is_array($value) ? $value : [$value] as $iri) {
            try {
                $resources[] = $this->iriConverter->getResourceFromIri((string) $iri, $iriConverterContext);
            } catch (InvalidArgumentException $exception) {
                if ($exception instanceof ItemNotFoundException && true === ($extraProperties['throw_not_found'] ?? false)) {
                    throw $exception;
                }

                $this->logger->notice(
                    message: 'Unable to resolve an IRI parameter value',
                    context: [
                        'exception' => $exception->getMessage(),
                    ]
                );
            }
        }

        $parameter->setValue(\is_array($value) || [] === $resources ? $resources : $resources[0]);

        return $operation;
    }
}
