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
use ApiPlatform\Core\Exception\FilterValidationException;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Util\RequestAttributesExtractor;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Validates query parameters depending on filter description.
 *
 * @author Julien Deniau <julien.deniau@gmail.com>
 */
final class QueryParameterValidateListener
{
    use FilterLocatorTrait;

    private $resourceMetadataFactory;

    private $validators;

    public function __construct(ResourceMetadataFactoryInterface $resourceMetadataFactory, ContainerInterface $filterLocator)
    {
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->setFilterLocator($filterLocator);

        $this->validators = [
            new Validator\Required(),
            new Validator\Bounds(),
            new Validator\Length(),
            new Validator\Pattern(),
            new Validator\Enum(),
            new Validator\MultipleOf(),
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        if (
            !$request->isMethodSafe()
            || !($attributes = RequestAttributesExtractor::extractAttributes($request))
            || !isset($attributes['collection_operation_name'])
            || 'get' !== ($operationName = $attributes['collection_operation_name'])
        ) {
            return;
        }

        $resourceMetadata = $this->resourceMetadataFactory->create($attributes['resource_class']);
        $resourceFilters = $resourceMetadata->getCollectionOperationAttribute($operationName, 'filters', [], true);

        $errorList = [];
        foreach ($resourceFilters as $filterId) {
            if (!$filter = $this->getFilter($filterId)) {
                continue;
            }

            foreach ($filter->getDescription($attributes['resource_class']) as $name => $data) {
                foreach ($this->validators as $validator) {
                    $errorList = array_merge($errorList, $validator->validate($name, $data, $request));
                }
            }
        }

        if ($errorList) {
            throw new FilterValidationException($errorList);
        }
    }

    // TODO grouper les filtres required dans une classe
    // avoir deux entités, une required, une pour le reste
    // maxItems	integer	See https://tools.ietf.org/html/draft-fge-json-schema-validation-00#section-5.3.2.
    // minItems	integer	See https://tools.ietf.org/html/draft-fge-json-schema-validation-00#section-5.3.3.
    // uniqueItems	boolean	See https://tools.ietf.org/html/draft-fge-json-schema-validation-00#section-5.3.4.
}
