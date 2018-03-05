<?php

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Dummy.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @ApiResource()
 * @ORM\Entity()
 */
class DummyValueObject
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
     * @var string The dummy name
     *
     * @ORM\Column
     * @Assert\NotBlank
     */
    private $foo;


    /**
     * @var string The dummy name
     *
     * @ORM\Column
     * @Assert\NotBlank
     */
    private $bar;

    /**
     * @var string The dummy name
     *
     * @ORM\Column(nullable=true)
     */
    private $baz;

    public function __construct(string $foo, string $bar)
    {
        $this->foo = $foo;
        $this->bar = $bar;
    }

    public function getId()
    {
        return $this->getId();
    }

    /**
     * @param string $baz
     */
    public function setBaz(string $baz)
    {
        $this->baz = $baz;
    }

    /**
     * @return string
     */
    public function getFoo(): string
    {
        return $this->foo;
    }

    /**
     * @return string
     */
    public function getBar(): string
    {
        return $this->bar;
    }

    /**
     * @return mixed
     */
    public function getBaz()
    {
        return $this->baz;
    }
}
