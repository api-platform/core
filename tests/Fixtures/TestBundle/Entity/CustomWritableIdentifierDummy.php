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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * Custom Writable Identifier Dummy.
 */
#[ApiResource(extraProperties: ['standard_put' => false])]
#[ORM\Entity]
class CustomWritableIdentifierDummy
{
    /**
     * @var string The special identifier
     */
    #[ORM\Column(name: 'slug', type: 'string', length: 30)]
    #[ORM\Id]
    private string $slug;
    /**
     * @var string The dummy name
     */
    #[ORM\Column(name: 'name', type: 'string', length: 30)]
    private string $name;

    /**
     * @param string $slug
     */
    public function setSlug($slug): void
    {
        $this->slug = $slug;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name): void
    {
        $this->name = $name;
    }
}
