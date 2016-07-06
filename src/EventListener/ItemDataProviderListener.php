<?php


namespace ApiPlatform\Core\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;

final class ItemDataProviderListener
{
    public function onKernelRequest(GetResponseEvent $event)
    {
        var_dump($event->getRequest()->attributes->get('id'));
        $event->getRequest()->attributes->set('data', 'fuck');
    }
}
