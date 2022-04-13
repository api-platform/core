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

namespace ApiPlatform\GraphQl\Resolver\Stage;

use ApiPlatform\Metadata\GraphQl\Operation;
use ApiPlatform\Validator\ValidatorInterface;

/**
 * Validate stage of GraphQL resolvers.
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class ValidateStage implements ValidateStageInterface
{
    private $validator;

    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke($object, string $resourceClass, Operation $operation, array $context): void
    {
        if (!($operation->canValidate() ?? true)) {
            return;
        }

        $this->validator->validate($object, $operation->getValidationContext() ?? []);
    }
}
