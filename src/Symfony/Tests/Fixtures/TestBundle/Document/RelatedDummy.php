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

namespace ApiPlatform\Symfony\Tests\Fixtures\TestBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document]
class RelatedDummy
{
    public function __construct(
        #[ODM\Id(type: 'int', strategy: 'none')]
        public ?int $id = null,
    ) {
    }
}
