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

namespace ApiPlatform\Core\Validator\EventListener;

use ApiPlatform\Core\Bridge\Symfony\Validator\Exception\ValidationException;
use ApiPlatform\Core\Event\EventInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Util\RequestAttributesExtractor;
use ApiPlatform\Core\Validator\ValidatorInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;

/**
 * Validates data.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ValidateListener
{
    private $validator;
    private $resourceMetadataFactory;

    public function __construct(ValidatorInterface $validator, ResourceMetadataFactoryInterface $resourceMetadataFactory)
    {
        $this->validator = $validator;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
    }

    /**
     * Validates data returned by the controller if applicable.
     *
     * @throws ValidationException
     *
     * @deprecated since version 2.5, to be removed in 3.0
     */
    public function onKernelView(GetResponseForControllerResultEvent $event): void
    {
        @trigger_error(sprintf('The method %s() is deprecated since 2.5 and will be removed in 3.0.', __METHOD__), E_USER_DEPRECATED);

        $this->handleEvent($event);
    }

    /**
     * Validates data returned by the controller if applicable.
     *
     * @throws ValidationException
     */
    public function handleEvent(/*EventInterface */$event): void
    {
        if ($event instanceof EventInterface) {
            $request = $event->getContext()['request'];
        } elseif ($event instanceof GetResponseForControllerResultEvent) {
            @trigger_error(sprintf('Passing an instance of "%s" as argument of "%s" is deprecated since 2.5 and will not be possible anymore in 3.0. Pass an instance of "%s" instead.', GetResponseForControllerResultEvent::class, __METHOD__, EventInterface::class), E_USER_DEPRECATED);

            $request = $event->getRequest();
        } else {
            return;
        }

        if (
            $request->isMethodSafe(false)
            || $request->isMethod('DELETE')
            || !($attributes = RequestAttributesExtractor::extractAttributes($request))
            || !$attributes['receive']
        ) {
            return;
        }

        $resourceMetadata = $this->resourceMetadataFactory->create($attributes['resource_class']);
        $inputMetadata = $resourceMetadata->getOperationAttribute($attributes, 'input', [], true);
        if (\array_key_exists('class', $inputMetadata) && null === $inputMetadata['class']) {
            return;
        }

        if ($event instanceof EventInterface) {
            $data = $event->getData();
        } elseif ($event instanceof GetResponseForControllerResultEvent) {
            $data = $event->getControllerResult();
        } else {
            return;
        }
        $validationGroups = $resourceMetadata->getOperationAttribute($attributes, 'validation_groups', null, true);
        $this->validator->validate($data, ['groups' => $validationGroups]);
    }
}
