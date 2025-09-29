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
use Symfony\Component\Validator\Constraints as Assert;

#[GetCollection(
    uriTemplate: '/translate_validation_error',
    parameters: [
        'name' => new QueryParameter(
            description: 'Nome della persona',
            constraints: [
                new Assert\NotBlank(),
            ]
        ),
        'surname' => new QueryParameter(
            description: 'Cognome della persona',
            required: true,
        ),
    ],
    provider: [self::class, 'provide']
)]
class TranslateValidationError
{
    public static function provide(Operation $operation, array $uriVariables = []): array
    {
        return [];
    }
}
