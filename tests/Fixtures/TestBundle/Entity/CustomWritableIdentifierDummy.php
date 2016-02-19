<?php

/*
 * This file is part of the API Platform Builder package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Builder\Tests\Fixtures\TestBundle\Entity;

use ApiPlatform\Builder\Annotation\Resource;
use Doctrine\ORM\Mapping as ORM;

/**
 * Custom writable identifier dummy.
 *
 * @Resource
 * @ORM\Entity
 */
class CustomWritableIdentifierDummy
{
    /**
     * @var string The special identifier.
     *
     * @ORM\Column(name="slug", type="string", length=30)
     * @ORM\Id
     */
    private $slug;

    /**
     * @var string The dummy name.
     *
     * @ORM\Column(name="name", type="string", length=30)
     */
    private $name;

    /**
     * @param string $slug
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;
    }

    /**
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
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
