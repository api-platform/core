<?php

declare(strict_types=1);

namespace ApiPlatform\Core\EventListener;

use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Util\RequestAttributesExtractor;
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
    private $defaultEnabledLocales;
    private $router;
    private $resourceMetadataFactory;

    public function __construct(ResourceMetadataFactoryInterface $resourceMetadataFactory, RequestContextAwareInterface $router = null, array $defaultEnabledLocales = [])
    {
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->defaultEnabledLocales = $defaultEnabledLocales;
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
        $enabledLocales = $this->extractEnabledLocales($request);

        // If no locale has been sent to the request, then try to guess the locale from the Accept-Language header
        if (!empty($enabledLocales) && null === $request->attributes->get('_locale') && $preferredLanguage = $request->getPreferredLanguage($enabledLocales)) {
            $request->setLocale($preferredLanguage);

            $this->setRouterContext($request);
        }
    }

    private function extractEnabledLocales(Request $request)
    {
        if ($attributes = RequestAttributesExtractor::extractAttributes($request)) {
            return $this
                ->resourceMetadataFactory
                ->create($attributes['resource_class'])
                ->getOperationAttribute($attributes, 'enabled_locales', $this->defaultEnabledLocales, true);
        }

        return $this->defaultEnabledLocales;
    }

    private function setRouterContext(Request $request): void
    {
        // Override Symfony default router context definition
        if (null !== $this->router) {
            $this->router->getContext()->setParameter('_locale', $request->getLocale());
        }
    }
}
