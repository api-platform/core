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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Document\Issue7797;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ApiResource(operations: [new Get()])]
#[ODM\Document]
class Plan
{
    #[ODM\Id(strategy: 'INCREMENT')]
    public ?int $id = null;

    #[ODM\Field]
    public string $name = '';
}
