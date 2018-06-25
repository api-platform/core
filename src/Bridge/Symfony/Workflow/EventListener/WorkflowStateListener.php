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

use ApiPlatform\Core\Bridge\Symfony\Workflow\WorkflowTransition;
use ApiPlatform\Core\Util\RequestAttributesExtractor;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Workflow\Registry;

/**
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
final class WorkflowStateListener
{
    private $workflows;
    private $serializer;

    public function __construct(Serializer $serializer, Registry $workflows)
    {
        $this->workflows = $workflows;
        $this->serializer = $serializer;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        if (!$request->isMethod(Request::METHOD_PATCH)
            || !($attributes = RequestAttributesExtractor::extractAttributes($request))
            || !isset($attributes['item_operation_name'])
            || 'workflow_state_patch' !== $attributes['item_operation_name']
        ) {
            return;
        }

        $workflowTransition = $this->serializer->deserialize($request->getContent(), WorkflowTransition::class, $request->attributes->get('_format'));

        if (!$transition = (string) $workflowTransition) {
            throw new BadRequestHttpException('Transition is required.');
        }

        $class = $request->attributes->get('data');
        $workflow = $this->workflows->get($class);

        // @TODO replace by violation of some sort (validator?)
        if (!$workflow->can($class, $transition)) {
            throw new HttpException(403, "Can not apply transition '$transition'");
        }

        $workflow->apply($class, $transition);
    }
}
