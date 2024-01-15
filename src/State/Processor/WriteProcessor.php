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
final class WriteProcessor implements ProcessorInterface
{
    use ClassInfoTrait;

    /**
     * @param ProcessorInterface<T1, T2> $processor
     * @param ProcessorInterface<T1, T2> $callableProcessor
     */
    public function __construct(private readonly ProcessorInterface $processor, private readonly ProcessorInterface $callableProcessor)
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        if (
            $data instanceof Response
            || !($operation->canWrite() ?? true)
            || !$operation->getProcessor()
        ) {
            return $this->processor->process($data, $operation, $uriVariables, $context);
        }

        return $this->processor->process($this->callableProcessor->process($data, $operation, $uriVariables, $context), $operation, $uriVariables, $context);
    }
}
