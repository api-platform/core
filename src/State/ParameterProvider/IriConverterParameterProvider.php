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
 * @experimental
 *
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
        if (!($value = $parameter->getValue()) || $value instanceof ParameterNotFound) {
            return $operation;
        }

        $extraProperties = $parameter->getExtraProperties();
        $iriConverterContext = ['fetch_data' => $extraProperties['fetch_data'] ?? false];

        if (\is_array($value)) {
            $entities = [];
            foreach ($value as $v) {
                try {
                    $entities[] = $this->iriConverter->getResourceFromIri($v, $iriConverterContext);
                } catch (InvalidArgumentException|ItemNotFoundException $exception) {
                    if ($exception instanceof ItemNotFoundException && true === ($extraProperties['throw_not_found'] ?? false)) {
                        throw $exception;
                    }

                    $this->logger->error(
                        message: 'Operation failed due to an invalid argument or a missing item',
                        context: [
                            'exception' => $exception->getMessage(),
                        ]
                    );

                    break;
                }
            }

            $parameter->setValue($entities);

            return $operation;
        }

        try {
            $parameter->setValue($this->iriConverter->getResourceFromIri($value, $iriConverterContext));
        } catch (InvalidArgumentException|ItemNotFoundException $exception) {
            if ($exception instanceof ItemNotFoundException && true === ($extraProperties['throw_not_found'] ?? false)) {
                throw $exception;
            }

            $this->logger->error(
                message: 'Operation failed due to an invalid argument or a missing item',
                context: [
                    'exception' => $exception->getMessage(),
                ]
            );
        }

        return $operation;
    }
}
