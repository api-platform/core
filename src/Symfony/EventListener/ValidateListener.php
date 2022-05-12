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

namespace ApiPlatform\Symfony\EventListener;

use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Util\OperationRequestInitiatorTrait;
use ApiPlatform\Util\RequestAttributesExtractor;
use ApiPlatform\Validator\Exception\ValidationException;
use ApiPlatform\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ViewEvent;

/**
 * Validates data.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ValidateListener
{
    use OperationRequestInitiatorTrait;

    public const OPERATION_ATTRIBUTE_KEY = 'validate';

    private $validator;
    private $resourceMetadataFactory;

    public function __construct(ValidatorInterface $validator, ResourceMetadataCollectionFactoryInterface $resourceMetadataFactory)
    {
        $this->validator = $validator;
        $this->resourceMetadataCollectionFactory = $resourceMetadataFactory;
    }

    /**
     * Validates data returned by the controller if applicable.
     *
     * @throws ValidationException
     */
    public function onKernelView(ViewEvent $event): void
    {
        $controllerResult = $event->getControllerResult();
        $request = $event->getRequest();
        $operation = $this->initializeOperation($request);

        if (
            $controllerResult instanceof Response
            || $request->isMethodSafe()
            || $request->isMethod('DELETE')
            || !($attributes = RequestAttributesExtractor::extractAttributes($request))
        ) {
            return;
        }

        if (!$operation || !($operation->canValidate() ?? true)) {
            return;
        }

        $this->validator->validate($controllerResult, $operation->getValidationContext() ?? []);
    }
}
