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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonApi;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Validator\Exception\ValidationException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

#[ApiResource(
    shortName: 'JsonApiErrorProblem',
    formats: ['jsonapi' => ['application/vnd.api+json']],
    operations: [
        new Post(
            uriTemplate: '/jsonapi_validation_problem',
            processor: [self::class, 'processValidation'],
        ),
        new Post(
            uriTemplate: '/jsonapi_exception_problem',
            processor: [self::class, 'processBadRequest'],
        ),
    ],
)]
class ErrorProblem
{
    public string $name = '';

    public static function processValidation(): void
    {
        $root = new self();
        $violation = new ConstraintViolation(
            'This value should not be blank.',
            null,
            [],
            $root,
            'name',
            null,
        );

        throw new ValidationException(new ConstraintViolationList([$violation]));
    }

    public static function processBadRequest(): void
    {
        throw new BadRequestHttpException();
    }
}
