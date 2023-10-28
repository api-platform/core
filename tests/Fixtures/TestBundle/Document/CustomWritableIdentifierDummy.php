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

/**
 * Custom Writable Identifier Dummy.
 */
#[ApiResource]
#[ODM\Document]
class CustomWritableIdentifierDummy
{
    /**
     * @var string The special identifier
     */
    #[ODM\Id(strategy: 'NONE', type: 'string')]
    private ?string $slug = null;
    /**
     * @var string The dummy name
     */
    #[ODM\Field(name: 'name', type: 'string')]
    private ?string $name = null;

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
