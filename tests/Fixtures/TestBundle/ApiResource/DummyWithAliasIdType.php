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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Dummy for aliasIdType.
 *
 * @author Romain Herault <romain@rherault.dev>
 */
#[ApiResource(aliasIdType: true)]
class DummyWithAliasIdType
{
    /**
     * @var string The dummy name
     */
    #[ApiProperty(iris: ['https://schema.org/name'])]
    #[Assert\NotBlank]
    private string $name;

    public static function staticMethod(): void
    {
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
