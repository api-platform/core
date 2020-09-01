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

namespace ApiPlatform\Core\Tests\EventListener;

use ApiPlatform\Core\EventListener\AddEnabledLocalesListener;
use ApiPlatform\Core\Tests\ProphecyTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RequestContextAwareInterface;

/**
 * @author Grégoire Hébert <contact@gheb.dev>
 */
class AddEnabledLocalesListenerTest extends TestCase
{
    use ProphecyTrait;

    public function testRequestPreferredLocaleFromAcceptLanguageHeader()
    {
        $request = Request::create('/');
        $request->setDefaultLocale('de');
        $request->attributes->set('_api_resource_class', true);
        $request->headers->set('Accept-Language', ['Accept-Language: fr-FR,fr;q=0.9,en-GB;q=0.8,en;q=0.7,en-US;q=0.6,es;q=0.5']);

        $listener = new AddEnabledLocalesListener(null, ['de', 'fr']);
        $event = $this->getEvent($request);

        $listener->onKernelRequest($event);
        self::assertEquals('fr', $request->getLocale());
    }

    public function testWithRouterContextPreferredLocaleFromAcceptLanguageHeader()
    {
        $requestContext = $this->prophesize(RequestContext::class);

        $router = $this->prophesize(RequestContextAwareInterface::class);
        $router->getContext()->willReturn($requestContext)->shouldBeCalledTimes(1);


        $request = Request::create('/');
        $request->setDefaultLocale('de');
        $request->attributes->set('_api_resource_class', true);
        $request->headers->set('Accept-Language', ['Accept-Language: fr-FR,fr;q=0.9,en-GB;q=0.8,en;q=0.7,en-US;q=0.6,es;q=0.5']);

        $listener = new AddEnabledLocalesListener($router->reveal(), ['de', 'fr']);
        $event = $this->getEvent($request);

        $listener->onKernelRequest($event);

        $this->getProphet()->checkPredictions();
        $requestContext->setParameter('_locale', 'fr')->shouldHaveBeenCalledOnce();
//        $router->getContext()->shouldHaveBeenCalledOnce();
        self::assertEquals('fr', $request->getLocale());
    }

    public function testRequestSecondPreferredLocaleFromAcceptLanguageHeader()
    {
        $request = Request::create('/');
        $request->setDefaultLocale('de');
        $request->attributes->set('_api_resource_class', true);
        $request->headers->set('Accept-Language', ['Accept-Language: fr-FR,fr;q=0.9,en-GB;q=0.8,en;q=0.7,en-US;q=0.6,es;q=0.5']);

        $listener = new AddEnabledLocalesListener(null, ['de', 'en']);
        $event = $this->getEvent($request);

        $listener->onKernelRequest($event);
        self::assertEquals('en', $request->getLocale());
    }

    public function testRequestUnavailablePreferredLocaleFromAcceptLanguageHeader()
    {
        $request = Request::create('/');
        $request->setDefaultLocale('de');
        $request->attributes->set('_api_resource_class', true);
        $request->headers->set('Accept-Language', ['Accept-Language: fr-FR,fr;q=0.9,en-GB;q=0.8,en;q=0.7,en-US;q=0.6,es;q=0.5']);

        $listener = new AddEnabledLocalesListener(null, ['de', 'it']);
        $event = $this->getEvent($request);

        $listener->onKernelRequest($event);
        self::assertEquals('de', $request->getLocale());
    }

    public function testRequestNoLocaleFromAcceptLanguageHeader()
    {
        $request = Request::create('/');
        $request->setDefaultLocale('de');
        $request->attributes->set('_api_resource_class', true);
        $request->headers->set('Accept-Language', ['Accept-Language: fr-FR,fr;q=0.9,en-GB;q=0.8,en;q=0.7,en-US;q=0.6,es;q=0.5']);

        $listener = new AddEnabledLocalesListener();
        $event = $this->getEvent($request);

        $listener->onKernelRequest($event);
        self::assertEquals('de', $request->getLocale());
    }

    public function testRequestAttributeLocaleNotOverridenFromAcceptLanguageHeader()
    {
        $request = Request::create('/');
        $request->setDefaultLocale('de');
        $request->attributes->set('_api_resource_class', true);
        $request->attributes->set('_locale', 'it');
        $request->setLocale('it');
        $request->headers->set('Accept-Language', ['Accept-Language: fr-FR,fr;q=0.9,en-GB;q=0.8,en;q=0.7,en-US;q=0.6,es;q=0.5']);

        $listener = new AddEnabledLocalesListener(null, ['fr', 'en']);
        $event = $this->getEvent($request);

        $listener->onKernelRequest($event);
        self::assertEquals('it', $request->getLocale());
    }

    public function testRequestNotAnApiResource()
    {
        $request = Request::create('/');
        $request->setDefaultLocale('de');
        $request->headers->set('Accept-Language', ['Accept-Language: fr-FR,fr;q=0.9,en-GB;q=0.8,en;q=0.7,en-US;q=0.6,es;q=0.5']);

        $listener = new AddEnabledLocalesListener(null, ['fr']);
        $event = $this->getEvent($request);

        $listener->onKernelRequest($event);
        self::assertEquals('de', $request->getLocale());
    }

    private function getEvent(Request $request): RequestEvent
    {
        return new RequestEvent($this->prophesize(HttpKernelInterface::class)->reveal(), $request, HttpKernelInterface::MASTER_REQUEST);
    }
}
