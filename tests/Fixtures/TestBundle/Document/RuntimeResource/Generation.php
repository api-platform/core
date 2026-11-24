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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Document\RuntimeResource;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document(collection: 'runtime_generation')]
class Generation
{
    #[ODM\Id(strategy: 'NONE', type: 'int')]
    private int $id = 1;

    #[ODM\Field(type: 'int')]
    private int $value = 0;
}
