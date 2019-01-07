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
 * @ORM\Entity
 * @ApiResource(
 *     collectionOperations={
 *         "get"={"method"="GET"},
 *         "post"={"path"="dummy_validation.{_format}", "method"="POST"},
 *         "post_validation_groups"={"route_name"="post_validation_groups", "validation_groups"={"a"}},
 *         "post_validation_sequence"={"route_name"="post_validation_sequence", "validation_groups"="app.dummy_validation.group_generator"}
 *     }
 * )
 */
class DummyValidation
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
     * @var string|null The dummy name
     *
     * @ORM\Column(nullable=true)
     * @Assert\NotNull(groups={"a"})
     */
    private $name;

    /**
     * @var string|null The dummy title
     *
     * @ORM\Column(nullable=true)
     * @Assert\NotNull(groups={"b"})
     */
    private $title;

    /**
     * @var string The dummy code
     * @ORM\Column
     */
    private $code;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return DummyValidation
     */
    public function setId(int $id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string|null $name
     *
     * @return DummyValidation
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string|null $title
     *
     * @return DummyValidation
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param string $code
     *
     * @return DummyValidation
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }
}
