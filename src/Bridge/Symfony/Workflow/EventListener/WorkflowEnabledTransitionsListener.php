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

namespace ApiPlatform\Core\Bridge\Symfony\Workflow\EventListener;

use ApiPlatform\Core\Util\RequestAttributesExtractor;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Workflow\Registry;

/**
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
final class WorkflowEnabledTransitionsListener
{
    private $workflows;
    private $serializer;

    public function __construct(SerializerInterface $serializer, Registry $workflows)
    {
        $this->workflows = $workflows;
        $this->serializer = $serializer;
    }

    public function onKernelView(GetResponseForControllerResultEvent $event)
    {
        $request = $event->getRequest();

        if (!$request->isMethod(Request::METHOD_GET)
            || !($attributes = RequestAttributesExtractor::extractAttributes($request))
            || !isset($attributes['item_operation_name'])
            || 'workflow_state_get' !== $attributes['item_operation_name']
        ) {
            return;
        }

        $class = $request->attributes->get('data');
        $workflow = $this->workflows->get($class);
        $event->setResponse(new Response($this->serializer->serialize($workflow->getEnabledTransitions($class), $request->getRequestFormat())));
    }
}
