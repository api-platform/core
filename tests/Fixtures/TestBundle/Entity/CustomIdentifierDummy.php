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
 * Custom Identifier Dummy.
 *
 * @ApiResource
 * @ORM\Entity
 */
class CustomIdentifierDummy
{
    /**
     * @var int The custom identifier
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $customId;

    /**
     * @var string The dummy name
     *
     * @ORM\Column(length=30)
     */
    private $name;

    /**
     * @return int
     */
    public function getCustomId()
    {
        return $this->customId;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }
}
