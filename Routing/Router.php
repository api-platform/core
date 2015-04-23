<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Routing;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouterInterface;

/**
 * Router decorator.
 *
 * Kévin Dunglas <dunglas@gmail.com>
 */
class Router implements RouterInterface
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
     * {@inheritdoc}
     */
    public function setContext(RequestContext $context)
    {
        $this->router->setContext($context);
    }

    /**
     * {@inheritdoc}
     */
    public function getContext()
    {
        $this->router->getContext();
    }

    /**
     * {@inheritdoc}
     */
    public function getRouteCollection()
    {
        $this->router->getRouteCollection();
    }

    /*
     * {@inheritdoc}
     */
    public function match($pathInfo)
    {
        $baseContext = $this->router->getContext();

        $request = Request::create($pathInfo);
        $context = (new RequestContext())->fromRequest($request);
        $context->setPathInfo($pathInfo);

        try {
            $this->router->setContext($context);

            return $this->router->match($request->getPathInfo());
        } finally {
            $this->router->setContext($baseContext);
        }
    }

    /*
     * {@inheritdoc}
     */
    public function generate($name, $parameters = [], $referenceType = self::ABSOLUTE_PATH)
    {
        $baseContext = $this->router->getContext();

        try {
            $this->router->setContext(new RequestContext(
                '',
                'GET',
                $baseContext->getHost(),
                $baseContext->getScheme(),
                $baseContext->getHttpPort(),
                $baseContext->getHttpsPort()
            ));

            return $this->router->generate($name, $parameters, $referenceType);
        } finally {
            $this->router->setContext($baseContext);
        }
    }
}
