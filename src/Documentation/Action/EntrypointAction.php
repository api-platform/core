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

namespace ApiPlatform\Documentation\Action;

use ApiPlatform\Documentation\Entrypoint;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\State\ProviderInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Generates the API entrypoint.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class EntrypointAction
{
    public function __construct(
        private readonly ResourceNameCollectionFactoryInterface $resourceNameCollectionFactory,
        private readonly ProviderInterface $provider,
        private readonly ProcessorInterface $processor,
        private readonly array $documentationFormats = []
    ) {
    }

    public function __invoke(Request $request = null)
    {
        $context = ['request' => $request];
        $operation = new Get(outputFormats: $this->documentationFormats, read: true, serialize: true, class: Entrypoint::class, provider: fn () => new Entrypoint($this->resourceNameCollectionFactory->create()));
        $body = $this->provider->provide($operation, [], $context);

        return $this->processor->process($body, $operation, [], $context);
    }
}
