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
    private $sharedMaxage;
    private $vary;
    private $public;

    public function __construct(bool $etag = false, int $maxAge = null, int $sharedMaxAge = null, array $vary = null, bool $public = null)
    {
        $this->etag = $etag;
        $this->maxAge = $maxAge;
        $this->sharedMaxage = $sharedMaxAge;
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

        if ($this->etag) {
            $response->setEtag(md5($response->getContent()));
        }

        if (null !== $this->maxAge) {
            $response->setMaxAge($this->maxAge);
        }

        if (null !== $this->vary) {
            $response->setVary(array_diff($this->vary, $response->getVary()), false);
        }

        if (null !== $this->sharedMaxage) {
            $response->setSharedMaxAge($this->sharedMaxage);
        }

        if (null !== $this->public) {
            $this->public ? $response->setPublic() : $response->setPrivate();
        }
    }
}
