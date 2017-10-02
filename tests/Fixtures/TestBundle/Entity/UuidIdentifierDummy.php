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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * Custom identifier dummy.
 *
 * @ApiResource
 * @ORM\Entity
 */
class UuidIdentifierDummy
{
    /**
     * @var string The custom identifier
     *
     * @ORM\Column(type="guid")
     * @ORM\Id
     */
    private $uuid;

    /**
     * @var string The dummy name
     *
     * @ORM\Column(length=30)
     */
    private $name;

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid)
    {
        $this->uuid = $uuid;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name)
    {
        $this->name = $name;
    }
}
