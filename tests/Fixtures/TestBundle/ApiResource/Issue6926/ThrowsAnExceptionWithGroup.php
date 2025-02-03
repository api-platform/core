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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue6926;

use ApiPlatform\Metadata\Post;
use Symfony\Component\Serializer\Attribute\Groups;

#[Post(
    uriTemplate: '/issue6926',
    processor: [self::class, 'process'],
    denormalizationContext: ['groups' => ['login_request:write']],
    normalizationContext: ['groups' => ['login_request:read']],
    errors: [Error::class]
)]
class ThrowsAnExceptionWithGroup
{
    #[Groups('login_request:read')]
    private ?string $id = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    public static function process(): void
    {
        throw new Error();
    }
}
