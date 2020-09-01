<?php

declare(strict_types=1);

namespace ApiPlatform\Core\EventListener;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\RequestContextAwareInterface;

/**
 * Choose the locale to use according to the Accept-Language header
 *
 * @author Grégoire Hébert <contact@gheb.dev>
 */
final class AddEnabledLocalesListener
{
    private $enabledLocales;
    private $router;

    public function __construct(RequestContextAwareInterface $router = null, array $enabledLocales = [])
    {
        $this->enabledLocales = $enabledLocales;
        $this->router = $router;
    }

    /**
     * Sets the applicable locale to the HttpFoundation Request.
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if (
        !($request->attributes->has('_api_resource_class')
            || $request->attributes->getBoolean('_api_respond', false)
            || $request->attributes->getBoolean('_graphql', false))
        ) {
            return;
        }

        $this->setLocale($request);
    }

    private function setLocale(Request $request): void
    {
        // If no locale has been sent to the request, then try to guess the locale from the Accept-Language header
        if (!empty($this->enabledLocales) && null === $request->attributes->get('_locale') && $preferredLanguage = $request->getPreferredLanguage($this->enabledLocales)) {
            $request->setLocale($preferredLanguage);

            $this->setRouterContext($request);
        }
    }

    private function setRouterContext(Request $request): void
    {
        // Override Symfony default router context definition
        if (null !== $this->router) {
            $this->router->getContext()->setParameter('_locale', $request->getLocale());
        }
    }
}
