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

use ApiPlatform\Metadata\NotExposed;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Symfony\Validator\Exception\ValidationException as ExceptionValidationException;
use Symfony\Component\Validator\ConstraintViolationList;

#[NotExposed(uriTemplate: '/post_with_uri_variables/{id}')]
#[Post(uriTemplate: '/post_with_uri_variables_and_no_provider/{id}', uriVariables: ['id'], processor: [PostWithUriVariables::class, 'process'])]
#[Post(uriTemplate: '/post_with_uri_variables/{id}', uriVariables: ['id'], provider: [PostWithUriVariables::class, 'provide'])]
final class PostWithUriVariables
{
    public function __construct(public readonly ?int $id = null)
    {
    }

    public static function process(): self
    {
        return new self(id: 1);
    }

    public static function provide(): void
    {
        throw new ExceptionValidationException(new ConstraintViolationList());
    }
}
