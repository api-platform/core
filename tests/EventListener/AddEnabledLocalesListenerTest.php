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
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
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
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceMetadata(
            null,
            null,
            null,
            null,
           null
        ));

        $request = Request::create('/');
        $request->setDefaultLocale('de');
        $request->attributes->set('_api_resource_class', 'Foo');
        $request->attributes->set('resource_class', true);
        $request->headers->set('Accept-Language', ['Accept-Language: fr-FR,fr;q=0.9,en-GB;q=0.8,en;q=0.7,en-US;q=0.6,es;q=0.5']);

        $listener = new AddEnabledLocalesListener($resourceMetadataFactoryProphecy->reveal(), null, ['de', 'fr']);
        $event = $this->getEvent($request);

        $listener->onKernelRequest($event);
        self::assertEquals('fr', $request->getLocale());
    }

    public function testRequestPreferredLocaleFromAcceptLanguageHeaderFromItemOperation()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceMetadata(
            null,
            null,
            null,
            ['get' => ['enabled_locales' => ['mi','it']]],
            null
        ));

        $request = Request::create('/');
        $request->setDefaultLocale('de');
        $request->attributes->set('_api_resource_class', 'Foo');
        $request->attributes->set('_api_item_operation_name', 'get');
        $request->attributes->set('resource_class', true);
        $request->headers->set('Accept-Language', ['Accept-Language: fr-FR,fr;q=0.9,en-GB;q=0.8,en;q=0.7,en-US;q=0.6,es;q=0.5,mi;q=0.4']);

        $listener = new AddEnabledLocalesListener($resourceMetadataFactoryProphecy->reveal(), null, ['de', 'fr']);
        $event = $this->getEvent($request);

        $listener->onKernelRequest($event);
        self::assertEquals('mi', $request->getLocale());
    }

    public function testRequestPreferredLocaleFromAcceptLanguageHeaderFromCollectionOperation()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceMetadata(
            null,
            null,
            null,
            null,
            ['get' => ['enabled_locales' => ['mi','it']]]
        ));

        $request = Request::create('/');
        $request->setDefaultLocale('de');
        $request->attributes->set('_api_resource_class', 'Foo');
        $request->attributes->set('_api_collection_operation_name', 'get');
        $request->attributes->set('resource_class', true);
        $request->headers->set('Accept-Language', ['Accept-Language: fr-FR,fr;q=0.9,en-GB;q=0.8,en;q=0.7,en-US;q=0.6,es;q=0.5,mi;q=0.4']);

        $listener = new AddEnabledLocalesListener($resourceMetadataFactoryProphecy->reveal(), null, ['de', 'fr']);
        $event = $this->getEvent($request);

        $listener->onKernelRequest($event);
        self::assertEquals('mi', $request->getLocale());
    }

    public function testRequestPreferredLocaleFromAcceptLanguageHeaderFromSubressourceOperation()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceMetadata(
            null,
            null,
            null,
            null,
            null,
            null,
            ['get' => ['enabled_locales' => ['mi','it']]]
        ));

        $request = Request::create('/');
        $request->setDefaultLocale('de');
        $request->attributes->set('_api_resource_class', 'Foo');
        $request->attributes->set('_api_subresource_operation_name', 'get');
        $request->attributes->set('resource_class', true);
        $request->headers->set('Accept-Language', ['Accept-Language: fr-FR,fr;q=0.9,en-GB;q=0.8,en;q=0.7,en-US;q=0.6,es;q=0.5,mi;q=0.4']);

        $listener = new AddEnabledLocalesListener($resourceMetadataFactoryProphecy->reveal(), null, ['de', 'fr']);
        $event = $this->getEvent($request);

        $listener->onKernelRequest($event);
        self::assertEquals('mi', $request->getLocale());
    }

    public function testRequestPreferredLocaleFromAcceptLanguageHeaderFalledBackOnResource()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceMetadata(
            null,
            null,
            null,
            null,
            null,
            ['enabled_locales' => ['mi','it']]
        ));

        $request = Request::create('/');
        $request->setDefaultLocale('de');
        $request->attributes->set('_api_resource_class', 'Foo');
        $request->attributes->set('_api_collection_operation_name', 'get');
        $request->attributes->set('resource_class', true);
        $request->headers->set('Accept-Language', ['Accept-Language: fr-FR,fr;q=0.9,en-GB;q=0.8,en;q=0.7,en-US;q=0.6,es;q=0.5,mi;q=0.4']);

        $listener = new AddEnabledLocalesListener($resourceMetadataFactoryProphecy->reveal(), null, ['de', 'fr']);
        $event = $this->getEvent($request);

        $listener->onKernelRequest($event);
        self::assertEquals('mi', $request->getLocale());
    }

    public function testWithRouterContextPreferredLocaleFromAcceptLanguageHeader()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceMetadata(
            null,
            null,
            null,
            null,
            null
        ));

        $requestContextProphecy = $this->prophesize(RequestContext::class);
        $requestContextProphecy->setParameter('_locale', 'fr')->shouldBeCalledOnce();

        $routerProphecy = $this->prophesize(RequestContextAwareInterface::class);
        $routerProphecy->getContext()->willReturn($requestContextProphecy)->shouldBeCalledOnce();

        $request = Request::create('/');
        $request->setDefaultLocale('de');
        $request->attributes->set('_api_resource_class', 'Foo');
        $request->headers->set('Accept-Language', ['Accept-Language: fr-FR,fr;q=0.9,en-GB;q=0.8,en;q=0.7,en-US;q=0.6,es;q=0.5']);

        $listener = new AddEnabledLocalesListener($resourceMetadataFactoryProphecy->reveal(), $routerProphecy->reveal(), ['de', 'fr']);
        $event = $this->getEvent($request);

        $listener->onKernelRequest($event);

        self::assertEquals('fr', $request->getLocale());
    }

    public function testRequestSecondPreferredLocaleFromAcceptLanguageHeader()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceMetadata(
            null,
            null,
            null,
            null,
            null
        ));

        $request = Request::create('/');
        $request->setDefaultLocale('de');
        $request->attributes->set('_api_resource_class', 'Foo');
        $request->headers->set('Accept-Language', ['Accept-Language: fr-FR,fr;q=0.9,en-GB;q=0.8,en;q=0.7,en-US;q=0.6,es;q=0.5']);

        $listener = new AddEnabledLocalesListener($resourceMetadataFactoryProphecy->reveal(), null, ['de', 'en']);
        $event = $this->getEvent($request);

        $listener->onKernelRequest($event);
        self::assertEquals('en', $request->getLocale());
    }

    public function testRequestUnavailablePreferredLocaleFromAcceptLanguageHeader()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceMetadata(
            null,
            null,
            null,
            null,
            null
        ));

        $request = Request::create('/');
        $request->setDefaultLocale('de');
        $request->attributes->set('_api_resource_class', 'Foo');
        $request->headers->set('Accept-Language', ['Accept-Language: fr-FR,fr;q=0.9,en-GB;q=0.8,en;q=0.7,en-US;q=0.6,es;q=0.5']);

        $listener = new AddEnabledLocalesListener($resourceMetadataFactoryProphecy->reveal(), null, ['de', 'it']);
        $event = $this->getEvent($request);

        $listener->onKernelRequest($event);
        self::assertEquals('de', $request->getLocale());
    }

    public function testRequestNoLocaleFromAcceptLanguageHeader()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->shouldNotBeCalled();

        $request = Request::create('/');
        $request->setDefaultLocale('de');
        $request->attributes->set('_api_resource_class', 'Foo');
        $request->headers->set('Accept-Language', ['Accept-Language: fr-FR,fr;q=0.9,en-GB;q=0.8,en;q=0.7,en-US;q=0.6,es;q=0.5']);

        $listener = new AddEnabledLocalesListener($resourceMetadataFactoryProphecy->reveal());
        $event = $this->getEvent($request);

        $listener->onKernelRequest($event);
        self::assertEquals('de', $request->getLocale());
    }

    public function testRequestAttributeLocaleNotOverridenFromAcceptLanguageHeader()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceMetadata(
            null,
            null,
            null,
            null,
            null
        ));

        $request = Request::create('/');
        $request->setDefaultLocale('de');
        $request->attributes->set('_api_resource_class', 'Foo');
        $request->attributes->set('_locale', 'it');
        $request->setLocale('it');
        $request->headers->set('Accept-Language', ['Accept-Language: fr-FR,fr;q=0.9,en-GB;q=0.8,en;q=0.7,en-US;q=0.6,es;q=0.5']);

        $listener = new AddEnabledLocalesListener($resourceMetadataFactoryProphecy->reveal(), null, ['fr', 'en']);
        $event = $this->getEvent($request);

        $listener->onKernelRequest($event);
        self::assertEquals('it', $request->getLocale());
    }

    public function testRequestNotAnApiResource()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->shouldNotBeCalled();

        $request = Request::create('/');
        $request->setDefaultLocale('de');
        $request->headers->set('Accept-Language', ['Accept-Language: fr-FR,fr;q=0.9,en-GB;q=0.8,en;q=0.7,en-US;q=0.6,es;q=0.5']);

        $listener = new AddEnabledLocalesListener($resourceMetadataFactoryProphecy->reveal(), null, ['fr']);
        $event = $this->getEvent($request);

        $listener->onKernelRequest($event);
        self::assertEquals('de', $request->getLocale());
    }

    private function getEvent(Request $request): RequestEvent
    {
        return new RequestEvent($this->prophesize(HttpKernelInterface::class)->reveal(), $request, HttpKernelInterface::MASTER_REQUEST);
    }
}
