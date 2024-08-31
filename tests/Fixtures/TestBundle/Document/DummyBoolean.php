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

#[ApiResource]
#[ODM\Document]
class DummyBoolean
{
    #[ODM\Id(strategy: 'INCREMENT', type: 'int', nullable: true)]
    private ?int $id = null;

    public function __construct(
        #[ODM\Field(type: 'bool', nullable: true)] private bool $isDummyBoolean,
    ) {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function isDummyBoolean(): bool
    {
        return $this->isDummyBoolean;
    }
}
