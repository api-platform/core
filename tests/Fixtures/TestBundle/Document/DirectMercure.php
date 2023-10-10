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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Document;

use ApiPlatform\Metadata\ApiResource;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ApiResource(mercure: true, extraProperties: ['standard_put' => false])]
#[ODM\Document]
class DirectMercure
{
    #[ODM\Id(strategy: 'INCREMENT', type: 'int')]
    public $id;
    #[ODM\Field(type: 'string')]
    public $name;
}
