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

use ApiPlatform\Metadata\Post;
use ApiPlatform\Validator\Exception\ValidationException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\ConstraintViolationList;

#[Post(processor: [ValidationExceptionProblem::class, 'provide'])]
#[Post(uriTemplate: '/exception_problems', processor: [ValidationExceptionProblem::class, 'provideException'])]
#[Post(uriTemplate: '/exception_problems_with_compatibility', processor: [ValidationExceptionProblem::class, 'provideException'])]
#[Post(uriTemplate: '/exception_problems_without_prefix', normalizationContext: ['hydra_prefix' => false], processor: [ValidationExceptionProblem::class, 'provideException'])]
class ValidationExceptionProblem
{
    public static function provide(): void
    {
        throw new ValidationException(new ConstraintViolationList());
    }

    public static function provideException(): void
    {
        throw new BadRequestHttpException();
    }
}
