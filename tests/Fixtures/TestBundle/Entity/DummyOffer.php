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
 * Dummy Offer.
 * https://github.com/api-platform/core/issues/1107.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 *
 * @ApiResource
 * @ORM\Entity
 */
class DummyOffer
{
    /**
     * @var int The id
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var int The dummy aggregate offer value
     *
     * @ORM\Column(type="integer")
     */
    private $value;

    /**
     * Get id.
     *
     * @return id
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Get value.
     *
     * @return value
     */
    public function getValue(): int
    {
        return $this->value;
    }

    /**
     * Set value.
     *
     * @param value the value to set
     */
    public function setValue(int $value)
    {
        $this->value = $value;
    }
}
