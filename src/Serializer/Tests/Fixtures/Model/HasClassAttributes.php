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

namespace ApiPlatform\Serializer\Tests\Fixtures\Model;

use Symfony\Component\Serializer\Attribute\Context;
use Symfony\Component\Serializer\Attribute\Groups;

#[Groups(['classA', 'classB'])]
#[Context(normalizationContext: ['classNorm' => 'cn'], denormalizationContext: ['classDenorm' => 'cd'], groups: ['classGroup'])]
class HasClassAttributes
{
    public ?string $any = null;

    public ?string $other = null;
}
