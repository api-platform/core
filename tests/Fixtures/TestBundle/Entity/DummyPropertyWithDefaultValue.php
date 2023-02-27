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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity;

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
     * @var int|null
     *
     * @ORM\Id
     *
     * @ORM\Column(type="integer")
     *
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @Groups("dummy_read")
     */
    private $id;

    /**
     * @var string|null
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

    public function getId(): ?int
    {
        return $this->id;
    }
}
