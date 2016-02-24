<?php

/*
 * This file is part of the API Platform Builder package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Bridge\Symfony\Validator\EventListener;

use ApiPlatform\Core\Bridge\Symfony\Validator\Exception\ValidationException;
use ApiPlatform\Core\Metadata\Resource\Factory\ItemMetadataFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Validates data.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ViewListener
{
    private $itemMetadataFactory;
    private $validator;

    public function __construct(ItemMetadataFactoryInterface $itemMetadataFactory, ValidatorInterface $validator)
    {
        $this->itemMetadataFactory = $itemMetadataFactory;
        $this->validator = $validator;
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

        $resourceClass = $request->attributes->get('_resource_class');
        $itemOperationName = $request->attributes->get('_item_operation_name');
        $collectionOperationName = $request->attributes->get('_collection_operation_name');

        $method = $request->getMethod();
        if (
            !$resourceClass || (!$itemOperationName && !$collectionOperationName) ||
            (Request::METHOD_POST !== $method && Request::METHOD_PUT !== $method && Request::METHOD_PATCH !== $method)
        ) {
            return;
        }

        $data = $event->getControllerResult();

        $itemMetadata = $this->itemMetadataFactory->create($resourceClass);

        if ($collectionOperationName) {
            $validationGroups = $itemMetadata->getCollectionOperationAttribute($collectionOperationName, 'validation_groups');
        } else {
            $validationGroups = $itemMetadata->getItemOperationAttribute($itemOperationName, 'validation_groups');
        }

        if (!$validationGroups) {
            // Fallback to the resource
            $validationGroups = $itemMetadata->getAttributes()['validation_groups'] ?? null;
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
