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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\QueryParameter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\NotBlank;

#[GetCollection(
    uriTemplate: 'query_parameter_validate_before_read',
    parameters: [
        'search' => new QueryParameter(constraints: [new NotBlank()]),
        'sort[:property]' => new QueryParameter(constraints: [new NotBlank(), new Collection(['id' => new Choice(['asc', 'desc'])], allowMissingFields: true)]),
    ],
    provider: [self::class, 'provide']
)]
class ValidateParameterBeforeProvider
{
    public static function provide(Operation $operation, array $uriVariables = [], array $context = [])
    {
        if (!$context['request']->query->get('search')) {
            throw new \RuntimeException('Not supposed to happen');
        }

        return new JsonResponse(204);
    }
}
