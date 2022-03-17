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

namespace ApiPlatform\Symfony\Bundle\Processor;

use ApiPlatform\State\ChainProcessor;
use ApiPlatform\State\ProcessorInterface;

final class TraceableChainProcessor implements ProcessorInterface
{
    private $processors = [];
    private $processorsResponse = [];
    private $decorated;

    public function __construct(ProcessorInterface $processor)
    {
        if ($processor instanceof ChainProcessor) {
            $this->decorated = $processor;
            $this->processors = $processor->processors;
        }
    }

    public function getProcessorsResponse(): array
    {
        return $this->processorsResponse;
    }

    private function traceProcessors($data, array $context = [])
    {
        $found = false;
        foreach ($this->processors as $processor) {
            if (
                ($this->processorsResponse[\get_class($processor)] = $found ? false : $processor->supports($data, $context))
                &&
                !$found
            ) {
                $found = true;
            }
        }
    }

    public function resumable(?string $operationName = null, array $context = []): bool
    {
        return false;
    }

    public function process($data, array $uriVariables = [], ?string $operationName = null, array $context = [])
    {
        $this->traceProcessors($data, $context);

        return $this->decorated->process($data, $uriVariables, $operationName, $context);
    }

    public function supports($data, array $uriVariables = [], ?string $operationName = null, array $context = []): bool
    {
        return $this->decorated->supports($data, $uriVariables, $operationName, $context);
    }
}
