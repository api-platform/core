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
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * ProviderEntity.
 */
#[ApiResource]
#[Get]
#[GetCollection]
#[Post]
#[ODM\Document]
class ProviderEntity
{
    #[ODM\Id(strategy: 'INCREMENT', type: 'int')]
    private ?int $id = null;

    #[Assert\NotBlank]
    #[ODM\Field]
    private ?string $foo = null;

    /**
     * @return int
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFoo(): string
    {
        return $this->foo;
    }

    public function setFoo(string $foo): void
    {
        $this->foo = $foo;
    }
}
