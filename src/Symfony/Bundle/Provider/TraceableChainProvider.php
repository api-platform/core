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

namespace ApiPlatform\Symfony\Bundle\Provider;

use ApiPlatform\State\ChainProvider;
use ApiPlatform\State\ProviderInterface;

final class TraceableChainProvider implements ProviderInterface
{
    private $providers;
    private $context;
    private $providersResponse = [];
    private $decorated;

    public function __construct(ProviderInterface $provider)
    {
        if ($provider instanceof ChainProvider) {
            $this->decorated = $provider;
            $this->providers = $provider->providers;
        }
    }

    public function getProvidersResponse()
    {
        return $this->providersResponse;
    }

    public function getContext()
    {
        return $this->context;
    }

    private function traceProviders(string $resourceClass, array $uriVariables = [], ?string $operationName = null, array $context = [])
    {
        foreach ($this->providers as $provider) {
            $this->providersResponse[\get_class($provider)] = $provider->supports($resourceClass, $uriVariables, $operationName, $context);
        }
    }

    private function traceContext(array $context)
    {
        $this->context = $context;
    }

    public function provide(string $resourceClass, array $uriVariables = [], ?string $operationName = null, array $context = [])
    {
        $this->traceProviders($resourceClass, $uriVariables, $operationName, $context);
        $this->traceContext($context);

        return $this->decorated->provide($resourceClass, $uriVariables, $operationName, $context);
    }

    public function supports(string $resourceClass, array $uriVariables = [], ?string $operationName = null, array $context = []): bool
    {
        return $this->decorated->supports($resourceClass, $uriVariables, $operationName, $context);
    }
}
