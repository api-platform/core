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
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * DummyPropertyWithDefaultValue.
 *
 * @ORM\Entity
 *
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"dummy_read"}},
 *     "denormalization_context"={"groups"={"dummy_write"}}
 * })
 */
class DummyPropertyWithDefaultValue
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @Groups("dummy_read")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(nullable=true)
     *
     * @Groups({"dummy_read", "dummy_write"})
     */
    public $foo = 'foo';

    /**
     * @var string A dummy with a Doctrine default options
     *
     * @ORM\Column(options={"default"="default value"})
     */
    public $dummyDefaultOption;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
}
