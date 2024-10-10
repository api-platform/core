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

use ApiPlatform\Metadata\ApiProperty;
use Symfony\Component\Serializer\Attribute\Groups;

class HasRelation
{
    #[ApiProperty(serialize: [new Groups(['read'])])]
    public function relation(): Relation
    {
        return new Relation();
    }
}
