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

use ApiPlatform\Core\Exception\RuntimeException;
use ApiPlatform\Metadata\Operation;
use Psr\Container\ContainerInterface;

final class CallableProcessor implements ProcessorInterface
{
    private $locator;

    public function __construct(ContainerInterface $locator)
    {
        $this->locator = $locator;
    }

    /**
     * {@inheritDoc}
     */
    public function process($data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        if (!($processor = $operation->getProcessor())) {
            return;
        }

        if (\is_callable($processor)) {
            return $processor($data, $operation, $uriVariables, $context);
        }

        if (\is_string($processor)) {
            if (!$this->locator->has($processor)) {
                throw new RuntimeException(sprintf('Processor "%s" not found on operation "%s"', $processor, $operation->getName()));
            }

            /** @var ProcessorInterface */
            $processor = $this->locator->get($processor);

            return $processor->process($data, $operation, $uriVariables, $context);
        }
    }
}
