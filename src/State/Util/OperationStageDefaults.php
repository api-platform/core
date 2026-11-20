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

namespace ApiPlatform\State\Util;

use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\State\SerializerContextBuilderInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Resolves the stage flags left unset on an operation to their request-dependent defaults.
 *
 * @internal
 */
final class OperationStageDefaults
{
    private function __construct()
    {
    }

    public static function forProvider(HttpOperation $operation, Request $request): HttpOperation
    {
        if (null === $operation->canValidate()) {
            $operation = $operation->withValidate(!$request->isMethodSafe() && !$request->isMethod('DELETE'));
        }

        if (null === $operation->canRead()) {
            $operation = $operation->withRead($operation->getUriVariables() || $request->isMethodSafe());
        }

        if (null === $operation->canDeserialize()) {
            $operation = $operation->withDeserialize(\in_array($operation->getMethod(), ['POST', 'PUT', 'PATCH'], true));
        }

        $denormalizationContext = $operation->getDenormalizationContext() ?? [];
        if ($operation->canDeserialize() && !isset($denormalizationContext[SerializerContextBuilderInterface::ASSIGN_OBJECT_TO_POPULATE])) {
            $method = $operation->getMethod();
            $assignObjectToPopulate = 'POST' === $method
                || 'PATCH' === $method
                || ('PUT' === $method && !($operation->getExtraProperties()['standard_put'] ?? true));

            $operation = $operation->withDenormalizationContext($denormalizationContext + [SerializerContextBuilderInterface::ASSIGN_OBJECT_TO_POPULATE => $assignObjectToPopulate]);
        }

        return $operation;
    }

    public static function forProcessor(HttpOperation $operation, Request $request): HttpOperation
    {
        if (null === $operation->canWrite()) {
            $operation = $operation->withWrite(!$request->isMethodSafe());
        }

        if (null === $operation->canSerialize()) {
            $operation = $operation->withSerialize(true);
        }

        return $operation;
    }
}
