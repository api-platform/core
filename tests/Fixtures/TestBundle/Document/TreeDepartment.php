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
use ApiPlatform\Metadata\Get;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document]
#[ApiResource(operations: [new Get()])]
class TreeDepartment
{
    #[ODM\Id(strategy: 'INCREMENT', type: 'int')]
    private ?int $id = null;

    #[ODM\Field(type: 'string', nullable: true)]
    public ?string $name = null;

    /**
     * The department this one is part of.
     */
    #[ODM\ReferenceOne(targetDocument: self::class, storeAs: 'id', nullable: true)]
    public ?self $part = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}
