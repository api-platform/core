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
     */
    public function onKernelView(GetResponseForControllerResultEvent $event): void
    {
        $request = $event->getRequest();
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

        $validationGroups = $resourceMetadata->getOperationAttribute($attributes, 'validation_groups', null, true);
        $this->validator->validate($event->getControllerResult(), ['groups' => $validationGroups]);
    }
}
