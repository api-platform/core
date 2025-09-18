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

namespace ApiPlatform\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Util\ClassInfoTrait;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\State\StopwatchAwareInterface;
use ApiPlatform\State\StopwatchAwareTrait;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bridges persistence and the API system.
 *
 * @template T1
 * @template T2
 *
 * @implements ProcessorInterface<T1, T2>
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
final class WriteProcessor implements ProcessorInterface, StopwatchAwareInterface
{
    use ClassInfoTrait;
    use StopwatchAwareTrait;

    /**
     * @param ProcessorInterface<mixed, mixed> $processor
     * @param ProcessorInterface<mixed, mixed> $callableProcessor
     */
    public function __construct(private readonly ?ProcessorInterface $processor, private readonly ProcessorInterface $callableProcessor)
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        if (
            $data instanceof Response
            || !($operation->canWrite() ?? true)
            || !$operation->getProcessor()
        ) {
            return $this->processor ? $this->processor->process($data, $operation, $uriVariables, $context) : $data;
        }

        $this->stopwatch?->start('api_platform.processor.write');
        $data = $this->callableProcessor->process($data, $operation, $uriVariables, $context);
        $this->stopwatch?->stop('api_platform.processor.write');

        return $this->processor ? $this->processor->process($data, $operation, $uriVariables, $context) : $data;
    }
}
