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
 * Dummy with multiple identifiers.
 *
 * @ApiResource
 * @ORM\Entity
 */
class MultipleIdentifiersDummy
{
    /**
     * @var string The first identifier
     *
     * @ORM\Column(type="string")
     * @ORM\Id
     */
    private $firstKey;

    /**
     * @var string The first identifier
     *
     * @ORM\Column(type="string")
     * @ORM\Id
     */
    private $secondKey;

    public function getFirstKey(): string
    {
        return $this->firstKey;
    }

    public function setFirstKey(string $firstKey): void
    {
        $this->firstKey = $firstKey;
    }

    public function getSecondKey(): string
    {
        return $this->secondKey;
    }

    public function setSecondKey(string $secondKey): void
    {
        $this->secondKey = $secondKey;
    }
}
