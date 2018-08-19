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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\PHPCR;

use Symfony\Component\Serializer\Annotation\Groups;
use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * Parent Dummy.
 *
 * @author Alexandre Delplace <alexandre.delplacemille@gmail.com>
 *
 * @PHPCRODM\MappedSuperclass
 */
class ParentDummy
{
    /**
     * @var int The age
     *
     * @PHPCRODM\Field(type="integer")
     * @Groups({"friends"})
     */
    private $age;

    public function getAge()
    {
        return $this->age;
    }
}
