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

namespace ApiPlatform\OpenApi\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\OpenApi;
use ApiPlatform\State\ProviderInterface;

/**
 * @internal
 */
final class OpenApiProvider implements ProviderInterface
{
    public function __construct(private readonly OpenApiFactoryInterface $openApiFactory)
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): OpenApi
    {
        return $this->openApiFactory->__invoke($context);
    }
}
