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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document]
class Dummy
{
    #[ODM\ReferenceOne(targetDocument: RelatedDummy::class, storeAs: 'id', nullable: true)]
    public ?RelatedDummy $relatedDummy = null;

    /** @var Collection<int, RelatedDummy> */
    #[ODM\ReferenceMany(targetDocument: RelatedDummy::class, storeAs: 'id')]
    public Collection $relatedDummies;

    #[ODM\Field(type: 'string', nullable: true)]
    public ?string $name = null;

    public function __construct(
        #[ODM\Id(type: 'int', strategy: 'none')]
        public ?int $id = null,
    ) {
        $this->relatedDummies = new ArrayCollection();
    }
}
