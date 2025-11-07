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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Document\Issue7349;

use ApiPlatform\Metadata\ApiResource;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Foo7349.
 *
 * @author Maxime Valin <contact@maximeval.in>
 */

#[ApiResource]
#[ODM\Document]
class Foo7349
{
    /**
     * @var int id
     */
    #[ODM\Id(type: 'int', strategy: 'INCREMENT')]
    private int $id;

    #[ODM\Field(type: 'string')]
    private string $name;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }
}
