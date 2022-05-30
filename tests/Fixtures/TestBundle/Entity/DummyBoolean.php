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
 * @ORM\Entity
 */
#[ApiResource]
class DummyBoolean
{
    /**
     * @var int|null
     *
     * @ORM\Column(type="integer", nullable=true)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;
    /**
     * @var bool|null
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
