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

namespace ApiPlatform\Metadata\Tests\Fixtures\ApiResource;

use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\Ignore;

class DummyIgnoreProperty
{
    public $visibleWithoutGroup;

    #[Groups(['dummy'])]
    public $visibleWithGroup;

    #[Groups(['dummy'])]
    #[Ignore]
    public $ignored;
}
