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

namespace ApiPlatform\HttpCache\State;

use ApiPlatform\HttpCache\PurgerInterface;
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\UrlGeneratorInterface;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\State\UriVariablesResolverTrait;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sets the list of resources' IRIs included in this response in the configured cache tag HTTP header and/or "xkey" HTTP headers.
 *
 * By default the "Cache-Tags" HTTP header is used because it is supported by CloudFlare.
 *
 * @see https://developers.cloudflare.com/cache/how-to/purge-cache#add-cache-tag-http-response-headers
 *
 * The "xkey" is used because it is supported by Varnish.
 * @see https://docs.varnish-software.com/varnish-cache-plus/vmods/ykey/
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class AddTagsProcessor implements ProcessorInterface
{
    use UriVariablesResolverTrait;

    public function __construct(private readonly ProcessorInterface $decorated, private readonly IriConverterInterface $iriConverter, private readonly ?PurgerInterface $purger = null)
    {
    }

    /**
     * Adds the configured HTTP cache tag and "xkey" headers.
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        $response = $this->decorated->process($data, $operation, $uriVariables, $context);

        if (
            !($request = $context['request'] ?? null)
            || !$request->isMethodCacheable()
            || !$response instanceof Response
            || !$operation instanceof HttpOperation
            || !$response->isCacheable()
        ) {
            return $response;
        }

        $resources = $request->attributes->get('_resources', []);
        if ($operation instanceof CollectionOperationInterface) {
            // Allows to purge collections
            $uriVariables = $this->getOperationUriVariables($operation, $request->attributes->all(), $operation->getClass());
            $iri = $this->iriConverter->getIriFromResource($operation->getClass(), UrlGeneratorInterface::ABS_PATH, $operation, ['uri_variables' => $uriVariables]);

            $resources[$iri] = $iri;
        }

        if (!$resources) {
            return $response;
        }

        if (!$this->purger) {
            $response->headers->set('Cache-Tags', implode(',', $resources));

            return $response;
        }

        $headers = $this->purger->getResponseHeaders($resources);

        foreach ($headers as $key => $value) {
            $response->headers->set($key, $value);
        }

        return $response;
    }
}
