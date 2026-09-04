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
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\State\StopwatchAwareInterface;
use ApiPlatform\State\StopwatchAwareTrait;
use ApiPlatform\State\Util\LinkTemplateHeaderSerializer;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\WebLink\HttpHeaderSerializer;

/**
 * @template T1
 * @template T2
 *
 * @implements ProcessorInterface<T1, T2>
 */
final class AddLinkHeaderProcessor implements ProcessorInterface, StopwatchAwareInterface
{
    use StopwatchAwareTrait;

    /**
     * @param ProcessorInterface<T1, T2> $decorated
     */
    public function __construct(private readonly ProcessorInterface $decorated, private readonly ?HttpHeaderSerializer $serializer = new HttpHeaderSerializer(), private readonly ?LinkTemplateHeaderSerializer $templateSerializer = new LinkTemplateHeaderSerializer())
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        $response = $this->decorated->process($data, $operation, $uriVariables, $context);

        if (
            !($request = $context['request'] ?? null)
            || !$response instanceof Response
        ) {
            return $response;
        }

        $this->stopwatch?->start('api_platform.processor.add_link_header');
        // We add our header here as Symfony does it only for the main Request and we want it to be done on errors (sub-request) as well
        $linksProvider = $request->attributes->get('_api_platform_links');
        if ($links = $linksProvider?->getLinks()) {
            // Symfony's HttpHeaderSerializer skips templated links, they belong to the Link-Template header (RFC 9652)
            if ($this->serializer && null !== ($header = $this->serializer->serialize($links))) {
                $response->headers->set('Link', $header);
            }

            if ($this->templateSerializer && null !== ($header = $this->templateSerializer->serialize($links))) {
                $response->headers->set('Link-Template', $header);
            }
        }
        $this->stopwatch?->stop('api_platform.processor.add_link_header');

        return $response;
    }
}
