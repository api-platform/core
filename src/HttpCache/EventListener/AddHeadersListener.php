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

use ApiPlatform\Core\Util\RequestAttributesExtractor;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

/**
 * Configures cache HTTP headers for the current response.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @experimental
 */
final class AddHeadersListener
{
    private $etag;
    private $maxAge;
    private $sharedMaxAge;
    private $vary;
    private $public;

    public function __construct(bool $etag = false, int $maxAge = null, int $sharedMaxAge = null, array $vary = null, bool $public = null)
    {
        $this->etag = $etag;
        $this->maxAge = $maxAge;
        $this->sharedMaxAge = $sharedMaxAge;
        $this->vary = $vary;
        $this->public = $public;
    }

    public function onKernelResponse(FilterResponseEvent $event)
    {
        $request = $event->getRequest();
        if (!$request->isMethodCacheable() || !RequestAttributesExtractor::extractAttributes($request)) {
            return;
        }

        $response = $event->getResponse();

        if (!$response->getContent()) {
            return;
        }

        if (!$response->getEtag() && $this->etag) {
            $response->setEtag(md5($response->getContent()));
        }

        if (!$response->getMaxAge() && null !== $this->maxAge) {
            $response->setMaxAge($this->maxAge);
        }

        if (null !== $this->vary) {
            $response->setVary(array_diff($this->vary, $response->getVary()), false);
        }

        if (!$response->headers->hasCacheControlDirective('s-maxage') && null !== $this->sharedMaxAge) {
            $response->setSharedMaxAge($this->sharedMaxAge);
        }

        if (!$response->headers->hasCacheControlDirective('public') && !$response->headers->hasCacheControlDirective('private') && null !== $this->public) {
            $this->public ? $response->setPublic() : $response->setPrivate();
        }
    }
}
