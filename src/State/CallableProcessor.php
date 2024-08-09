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

use ApiPlatform\Metadata\Exception\RuntimeException;
use ApiPlatform\Metadata\Operation;
use Psr\Container\ContainerInterface;

/**
 * @template T1
 * @template T2
 *
 * @implements ProcessorInterface<T1, T2>
 */
final class CallableProcessor implements ProcessorInterface
{
    public function __construct(private readonly ?ContainerInterface $locator = null)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        if (!($processor = $operation->getProcessor())) {
            return $data;
        }

        if (\is_callable($processor)) {
            return $processor($data, $operation, $uriVariables, $context);
        }

        if (!$this->locator->has($processor)) {
            throw new RuntimeException(\sprintf('Processor "%s" not found on operation "%s"', $processor, $operation->getName()));
        }

        /** @var ProcessorInterface<T1, T2> $processorInstance */
        $processorInstance = $this->locator->get($processor);

        return $processorInstance->process($data, $operation, $uriVariables, $context);
    }
}
