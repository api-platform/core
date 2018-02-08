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

namespace ApiPlatform\Core\Filter;

use ApiPlatform\Core\Api\FilterLocatorTrait;
use ApiPlatform\Core\Bridge\Symfony\Validator\Exception\ValidationException;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Util\RequestAttributesExtractor;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface as SymfonyValidatorInterface;

/**
 * Validate query parameters depending on filter description.
 *
 * @author Julien Deniau <julien.deniau@gmail.com>
 */
class QueryParameterValidateListener
{
    use FilterLocatorTrait;

    private $resourceMetadataFactory;

    private $validator;

    public function __construct(ResourceMetadataFactoryInterface $resourceMetadataFactory, SymfonyValidatorInterface $validator, $filterLocator)
    {
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->validator = $validator;
        $this->setFilterLocator($filterLocator);
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        if (
            !$request->isMethodSafe(false)
            || !($attributes = RequestAttributesExtractor::extractAttributes($request))
            || !isset($attributes['collection_operation_name'])
            || 'get' !== ($operationName = $attributes['collection_operation_name'])
        ) {
            return;
        }

        $resourceMetadata = $this->resourceMetadataFactory->create($attributes['resource_class']);
        $resourceFilters = $resourceMetadata->getCollectionOperationAttribute($operationName, 'filters', [], true);

        foreach ($resourceFilters as $filterId) {
            if (!$filter = $this->getFilter($filterId)) {
                continue;
            }

            foreach ($filter->getDescription($attributes['resource_class']) as $name => $data) {
                if ($data['required'] ?? false) {
                    $requiredConstraint = new Assert\NotNull();
                    $requiredConstraint->message = sprintf('query parameter `%s` is required', $name);
                    $errorList = $this->validator->validate($request->query->get($name), $requiredConstraint);

                    if (count($errorList) > 0) {
                        throw new ValidationException($errorList);
                    }
                }
            }
        }
    }
}
