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

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
#[ApiResource(mercure: true)]
#[ODM\Document]
class DummyMercure
{
    #[ODM\Id(strategy: 'INCREMENT', type: 'int')]
    public $id;
    #[ODM\Field(type: 'string')]
    public $name;
    #[ODM\Field(type: 'string')]
    public $description;
    #[ODM\ReferenceOne(targetDocument: RelatedDummy::class, storeAs: 'id', nullable: true)]
    public $relatedDummy;
}
