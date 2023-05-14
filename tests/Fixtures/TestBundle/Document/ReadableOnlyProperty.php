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

#[ApiResource]
#[ODM\Document]
class ReadableOnlyProperty
{
    #[ODM\Id(strategy: 'INCREMENT', type: 'int')]
    private ?int $id = null;

    #[ApiProperty(writable: false)]
    #[ODM\Field]
    private string $name = 'Read only';

    public function __construct()
    {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setName($name): never
    {
        throw new \Exception('Can not write name.');
    }

    public function getName(): string
    {
        return $this->name;
    }
}
