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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Document\Issue5723;

use ApiPlatform\Metadata\ApiResource;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ApiResource]
#[ODM\Document]
class Issue5723Bar
{
    #[ODM\Id(strategy: 'INCREMENT', type: 'int')]
    private ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    #[ODM\Field(type: 'string')]
    public string $name;

    #[ODM\ReferenceOne(nullable: true, storeAs: 'id', targetDocument: Issue5723Foo::class, inversedBy: 'barsConverted')]
    public Issue5723Foo|null $fooConverted = null;
}
