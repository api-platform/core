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

namespace ApiPlatform\Core\HttpCache\EventListener;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\HttpCache\CacheTagsFormattingPurgerInterface;
use ApiPlatform\Core\HttpCache\PurgerInterface;
use ApiPlatform\Core\Util\RequestAttributesExtractor;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

/**
 * Sets the list of resources' IRIs included in this response in the "Cache-Tags" HTTP header.
 *
 * The "Cache-Tags" is used because it is supported by CloudFlare.
 *
 * @see https://support.cloudflare.com/hc/en-us/articles/206596608-How-to-Purge-Cache-Using-Cache-Tags-Enterprise-only-
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Yanick Witschi <yanick.witschi@terminal42.ch>
 *
 * @experimental
 */
final class AddTagsListener
{
    private $iriConverter;
    private $purger;
    private $debug;

    public function __construct(IriConverterInterface $iriConverter, PurgerInterface $purger, bool $debug = false)
    {
        $this->iriConverter = $iriConverter;
        $this->purger = $purger;
        $this->debug = $debug;
    }

    /**
     * Adds the "Cache-Tags" header.
     *
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        $request = $event->getRequest();
        $response = $event->getResponse();

        if (
            !$request->isMethodCacheable()
            || !$response->isCacheable()
            || (!$attributes = RequestAttributesExtractor::extractAttributes($request))
        ) {
            return;
        }

        $resources = $request->attributes->get('_resources');
        if (isset($attributes['collection_operation_name'])) {
            // Allows to purge collections
            $iri = $this->iriConverter->getIriFromResourceClass($attributes['resource_class']);
            $resources[$iri] = $iri;
        }

        if (!$resources) {
            return;
        }

        if ($this->debug) {
            $event->getResponse()->headers->set('Cache-Tags-Debug', implode(',', $resources));
        }

        if ($this->purger instanceof CacheTagsFormattingPurgerInterface) {
            $formatted = $this->purger->formatTags($resources);
        } else {
            $formatted = implode(',', $resources);
        }

        $event->getResponse()->headers->set('Cache-Tags', $formatted);
    }
}
