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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Parent Dummy.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Alexandre Delplace <alexandre.delplacemille@gmail.com>
 *
 * @ODM\MappedSuperclass
 */
class ParentDummy
{
    /**
     * @var int The age
     *
     * @ODM\Field(type="integer", nullable=true)
     * @Groups({"friends"})
     */
    private $age;

    public function getAge()
    {
        return $this->age;
    }

    public function setAge($age)
    {
        return $this->age = $age;
    }
}
