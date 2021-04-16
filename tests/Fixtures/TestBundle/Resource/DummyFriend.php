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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Resource;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Models\DummyFriend as DummyFriendModel;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Dummy Friend.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @ApiResource(dataModel=DummyFriendModel::class)
 */
class DummyFriend
{
    /**
     * @var int The id
     *
     * @ApiProperty(identifier=true)
     */
    private $id;

    /**
     * @var string The dummy name
     *
     * @Assert\NotBlank
     * @ApiProperty(iri="http://schema.org/name")
     * @Groups({"fakemanytomany", "friends"})
     */
    private $name;

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set id.
     *
     * @param int $id the value to set
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set name.
     *
     * @param string $name the value to set
     */
    public function setName($name)
    {
        $this->name = $name;
    }
}
