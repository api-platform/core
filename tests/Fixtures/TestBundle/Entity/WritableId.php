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
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @ApiResource
 * @ORM\Entity
 */
class WritableId
{
    /**
     * @ORM\Id
     * @Assert\Uuid
     * @ORM\Column(type="guid")
     */
    public $id;

    /**
     * @ORM\Column
     */
    public $name;
}
