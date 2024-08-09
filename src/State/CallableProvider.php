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

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Exception\ProviderNotFoundException;
use Psr\Container\ContainerInterface;

final class CallableProvider implements ProviderInterface
{
    public function __construct(private readonly ?ContainerInterface $locator = null)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if (\is_callable($provider = $operation->getProvider())) {
            return $provider($operation, $uriVariables, $context);
        }

        if ($this->locator && \is_string($provider)) {
            if (!$this->locator->has($provider)) {
                throw new ProviderNotFoundException(\sprintf('Provider "%s" not found on operation "%s"', $provider, $operation->getName()));
            }

            /** @var ProviderInterface $providerInstance */
            $providerInstance = $this->locator->get($provider);

            return $providerInstance->provide($operation, $uriVariables, $context);
        }

        throw new ProviderNotFoundException(\sprintf('Provider not found on operation "%s"', $operation->getName()));
    }
}
