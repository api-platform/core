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

namespace ApiPlatform\Core\Bridge\Symfony\Validator\EventListener;

use ApiPlatform\Core\Bridge\Symfony\Validator\Exception\ValidationException;
use ApiPlatform\Core\Exception\RuntimeException;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Util\RequestAttributesExtractor;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\Validator\Validator\ValidatorInterface;

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
     * @param GetResponseForControllerResultEvent $event
     *
     * @throws ValidationException
     */
    public function onKernelView(GetResponseForControllerResultEvent $event)
    {
        $request = $event->getRequest();
        try {
            $attributes = RequestAttributesExtractor::extractAttributes($request);
        } catch (RuntimeException $e) {
            return;
        }

        if ($request->isMethodSafe(false) || $request->isMethod(Request::METHOD_DELETE)) {
            return;
        }

        $data = $event->getControllerResult();
        $resourceMetadata = $this->resourceMetadataFactory->create($attributes['resource_class']);

        if (isset($attributes['collection_operation_name'])) {
            $validationGroups = $resourceMetadata->getCollectionOperationAttribute($attributes['collection_operation_name'], 'validation_groups');
        } else {
            $validationGroups = $resourceMetadata->getItemOperationAttribute($attributes['item_operation_name'], 'validation_groups');
        }

        if (!$validationGroups) {
            // Fallback to the resource
            $validationGroups = $resourceMetadata->getAttributes()['validation_groups'] ?? null;
        }

        if (is_callable($validationGroups)) {
            $validationGroups = call_user_func_array($validationGroups, [$data]);
        }

        $violations = $this->validator->validate($data, null, $validationGroups);
        if (0 !== count($violations)) {
            throw new ValidationException($violations);
        }
    }
}
