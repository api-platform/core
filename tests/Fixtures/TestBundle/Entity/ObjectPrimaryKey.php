<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * Planning.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @ApiResource
 * @ORM\Entity
 */
class ObjectPrimaryKey
{
    /**
     * @var DateTime
     *
     * @ORM\Column(type="date")
     * @ORM\Id
     */
    private $date;

    public function getDate(): DateTime
    {
        return $this->date;
    }

    public function setDate(DateTime $date)
    {
        $this->date = $date;
    }
}
