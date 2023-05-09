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
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\WebLink\HttpHeaderSerializer;

final class AddLinkHeaderProcessor implements ProcessorInterface
{
    public function __construct(private readonly ProcessorInterface $inner, private readonly ?HttpHeaderSerializer $serializer = new HttpHeaderSerializer())
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        $response = $this->inner->process($data, $operation, $uriVariables, $context);

        if (
            !($request = $context['request'] ?? null)
            || !$response instanceof Response
        ) {
            return $response;
        }

        // We add our header here as Symfony does it only for the main Request and we want it to be done on errors (sub-request) as well
        $linksProvider = $request->attributes->get('_links');
        if ($this->serializer && ($links = $linksProvider->getLinks())) {
            $response->headers->set('Link', $this->serializer->serialize($links));
            // We don't want Symfony WebLink component do add links twice
            $request->attributes->set('_links', []);
        }

        return $response;
    }
}
