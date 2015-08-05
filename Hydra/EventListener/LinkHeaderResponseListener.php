<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Hydra\EventListener;

use Dunglas\ApiBundle\JsonLd\ContextBuilder;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * Adds the HTTP Link header pointing to the Hydra documentation.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class LinkHeaderResponseListener
{
    /**
     * @var RouterInterface
     */
    private $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    /**
     * Sends the Hydra header on each response.
     *
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        $event->getResponse()->headers->set('Link', sprintf(
            '<%s>; rel="%sapiDocumentation"',
            $this->router->generate('api_hydra_vocab', [], UrlGeneratorInterface::ABSOLUTE_URL), ContextBuilder::HYDRA_NS)
        );
    }
}
