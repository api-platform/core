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

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Parent Dummy.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @ORM\MappedSuperclass
 */
class ParentDummy
{
    /**
     * @var int The age
     *
     * @ORM\Column(type="integer", nullable=true)
     * @Groups({"friends"})
     */
    private $age;

    public function getAge()
    {
        return $this->age;
    }
}
