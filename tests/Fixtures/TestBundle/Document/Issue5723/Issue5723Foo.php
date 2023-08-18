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
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ApiResource]
#[ODM\Document]
class Issue5723Foo
{
    #[ODM\Id(strategy: 'INCREMENT', type: 'int')]
    private ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    #[ODM\Field(type: 'string')]
    public string $name;

    #[ODM\ReferenceMany(targetDocument: Issue5723Bar::class, mappedBy: 'fooConverted')]
    public Collection|iterable|null $barsConverted = null;

    #[ODM\ReferenceOne(nullable: true, storeAs: 'id', targetDocument: Issue5723Bar::class)]
    public Issue5723Bar|null $barConverted = null;
}
