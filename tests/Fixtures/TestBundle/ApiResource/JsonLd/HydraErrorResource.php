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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonLd;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Validator\Exception\ValidationException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

#[ApiResource(
    shortName: 'JsonLdHydraError',
    operations: [
        new Post(
            uriTemplate: '/jsonld_hydra_errors_bad_request',
            processor: [self::class, 'throwBadRequest'],
        ),
        new Post(
            uriTemplate: '/jsonld_hydra_errors_validation',
            processor: [self::class, 'throwValidation'],
        ),
        new Post(
            uriTemplate: '/jsonld_hydra_errors_no_prefix',
            normalizationContext: ['hydra_prefix' => false],
            processor: [self::class, 'throwBadRequest'],
        ),
        new Patch(
            uriTemplate: '/jsonld_hydra_errors_patch_only',
            processor: [self::class, 'throwBadRequest'],
        ),
    ],
)]
class HydraErrorResource
{
    public static function throwBadRequest(): void
    {
        throw new BadRequestHttpException();
    }

    public static function throwValidation(): void
    {
        $list = new ConstraintViolationList([
            new ConstraintViolation(
                'This value should not be blank.',
                null,
                [],
                null,
                'name',
                null,
                null,
                'c1051bb4-d103-4f74-8988-acbcafc7fdc3',
            ),
        ]);

        throw new ValidationException($list);
    }
}
