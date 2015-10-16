<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\EventListener;

use Dunglas\ApiBundle\Exception\ValidationException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Validates data.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ValidationViewListener
{
    /**
     * @var ValidatorInterface
     */
    private $validator;

    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    /**
     * Validates data returned by the controller if applicable.
     *
     * @param GetResponseForControllerResultEvent $event
     *
     * @return mixed
     *
     * @throws ValidationException
     */
    public function onKernelView(GetResponseForControllerResultEvent $event)
    {
        $resourceType = $event->getRequest()->attributes->get('_resource_type');
        if (!$resourceType || !in_array($event->getRequest()->getMethod(), [Request::METHOD_POST, Request::METHOD_PUT])) {
            return;
        }

        $violations = $this->validator->validate($event->getControllerResult(), null, $resourceType->getValidationGroups());
        if (0 !== count($violations)) {
            throw new ValidationException($violations);
        }
    }
}
