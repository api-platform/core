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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\PatchRequired;

use ApiPlatform\Metadata\Patch;
use Symfony\Component\Validator\Constraints\NotNull;

#[Patch(uriTemplate: '/patch_required_stuff', provider: [self::class, 'provide'])]
final class PatchMe
{
    public ?string $a = null;
    #[NotNull]
    public ?string $b = null;

    public static function provide(): self
    {
        return new self();
    }
}
