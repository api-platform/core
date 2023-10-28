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
 * Custom identifier dummy.
 */
#[ApiResource]
#[ODM\Document]
class UuidIdentifierDummy
{
    /**
     * @var string The custom identifier
     */
    #[ODM\Id(strategy: 'none', type: 'string')]
    private ?string $uuid = null;
    /**
     * @var string The dummy name
     */
    #[ODM\Field]
    private ?string $name = null;

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid): void
    {
        $this->uuid = $uuid;
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
