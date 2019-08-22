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

namespace ApiPlatform\Core\GraphQl\Resolver\Stage;

use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Validator\Exception\ValidationException;
use ApiPlatform\Core\Validator\ValidatorInterface;
use GraphQL\Error\Error;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * Validate stage of GraphQL resolvers.
 *
 * @experimental
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class ValidateStage implements ValidateStageInterface
{
    private $resourceMetadataFactory;
    private $validator;

    public function __construct(ResourceMetadataFactoryInterface $resourceMetadataFactory, ValidatorInterface $validator)
    {
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->validator = $validator;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke($object, string $resourceClass, string $operationName, array $context): void
    {
        $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
        if (!$resourceMetadata->getGraphqlAttribute($operationName, 'validate', true, true)) {
            return;
        }

        $validationGroups = $resourceMetadata->getGraphqlAttribute($operationName, 'validation_groups', null, true);
        try {
            $this->validator->validate($object, ['groups' => $validationGroups]);
        } catch (ValidationException $e) {
            /** @var ResolveInfo $info */
            $info = $context['info'];

            throw Error::createLocatedError($e->getMessage(), $info->fieldNodes, $info->path);
        }
    }
}
