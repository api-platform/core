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

namespace ApiPlatform\Hydra\State;

use ApiPlatform\JsonLd\ContextBuilder;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\UrlGeneratorInterface;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\State\Util\CorsTrait;
use Symfony\Component\WebLink\GenericLinkProvider;
use Symfony\Component\WebLink\Link;

/**
 * @template T1
 * @template T2
 *
 * @implements ProcessorInterface<T1, T2>
 */
final class HydraLinkProcessor implements ProcessorInterface
{
    use CorsTrait;

    /**
     * @param ProcessorInterface<T1, T2> $decorated
     */
    public function __construct(private readonly ProcessorInterface $decorated, private readonly UrlGeneratorInterface $urlGenerator)
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (!($request = $context['request'] ?? null) || !$operation instanceof HttpOperation || $this->isPreflightRequest($request)) {
            return $this->decorated->process($data, $operation, $uriVariables, $context);
        }

        $apiDocUrl = $this->urlGenerator->generate('api_doc', ['_format' => 'jsonld'], UrlGeneratorInterface::ABS_URL);
        $linkProvider = $request->attributes->get('_api_platform_links') ?? new GenericLinkProvider();

        foreach ($operation->getLinks() ?? [] as $link) {
            $linkProvider = $linkProvider->withLink($link);
        }

        $link = new Link(ContextBuilder::HYDRA_NS.'apiDocumentation', $apiDocUrl);
        $request->attributes->set('_api_platform_links', $linkProvider->withLink($link));

        return $this->decorated->process($data, $operation, $uriVariables, $context);
    }
}
