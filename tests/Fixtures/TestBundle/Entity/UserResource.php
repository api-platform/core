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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Tests\Fixtures\TestBundle\Dto\UserResetPasswordDto;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(
 *     collectionOperations={
 *         "post"={
 *             "method"="POST",
 *             "path"="/user-reset-password",
 *             "input"=UserResetPasswordDto::class
 *         }
 *     },
 *     itemOperations={}
 * )
 */
final class UserResource
{
    /**
     * @Assert\NotBlank
     */
    public string $username;
}
