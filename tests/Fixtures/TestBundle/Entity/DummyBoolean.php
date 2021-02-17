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
 * @ApiResource
 * @ORM\Entity
 */
class DummyBoolean
{
    /**
     * @var int
     *
     * @ORM\Column(type="integer", nullable=true)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $isDummyBoolean;

    public function __construct(bool $isDummyBoolean)
    {
        $this->isDummyBoolean = $isDummyBoolean;
    }

    public function getId()
    {
        return $this->id;
    }

    public function isDummyBoolean(): bool
    {
        return $this->isDummyBoolean;
    }
}
