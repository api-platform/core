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

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document]
#[ApiResource(
    uriTemplate: '/issue_7469_dummies/{id}',
)]
class Issue7469Dummy
{
    #[ODM\Id]
    #[ApiProperty(identifier: true)]
    public ?string $id = null;

    #[ODM\Field(type: 'string')]
    public string $name;
}
