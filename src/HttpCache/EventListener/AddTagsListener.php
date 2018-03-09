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
use ApiPlatform\Core\HttpCache\CacheTagsFormatterInterface;
use ApiPlatform\Core\HttpCache\CsvFormatter;
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
    private $tagsFormatter;

    public function __construct(IriConverterInterface $iriConverter, CacheTagsFormatterInterface $tagsFormatter = null)
    {
        $this->iriConverter = $iriConverter;
        $this->tagsFormatter = $tagsFormatter;

        // BC
        if (null === $this->tagsFormatter) {
            @trigger_error('Passing no implementation of the CacheTagsFormatterInterface is deprecated since version 2.3 and will be removed in 3.0.', E_USER_DEPRECATED);
            $this->tagsFormatter = new CsvFormatter();
        }
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

        $event->getResponse()->headers->set('Cache-Tags',
            $this->tagsFormatter->formatTags($resources)
        );
    }
}
