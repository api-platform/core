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

final class CallableProvider implements ProviderInterface
{
    private $locator;

    public function __construct(ContainerInterface $locator)
    {
        $this->locator = $locator;
    }

    /**
     * {@inheritDoc}
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = [])
    {
        if (\is_callable($provider = $operation->getProvider())) {
            return $provider($operation, $uriVariables, $context);
        }

        if (\is_string($provider)) {
            if (!$this->locator->has($provider)) {
                throw new RuntimeException(sprintf('Provider "%s" not found on operation "%s"', $provider, $operation->getName()));
            }

            /** @var ProviderInterface */
            $provider = $this->locator->get($provider);

            return $provider->provide($operation, $uriVariables, $context);
        }

        throw new RuntimeException(sprintf('Provider not found on operation "%s"', $operation->getName()));
    }
}
