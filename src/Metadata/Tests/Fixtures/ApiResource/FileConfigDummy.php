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

namespace ApiPlatform\Metadata\Tests\Fixtures\ApiResource;

/**
 * File Config Dummy.
 */
class FileConfigDummy
{
    final public const HYDRA_TITLE = 'File config Dummy';
    /**
     * @var int The id
     */
    private ?int $id = null;
    /**
     * @var string The dummy name
     */
    private $name;
    /**
     * @var string
     */
    private $foo;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setName($name): void
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setFoo($foo): void
    {
        $this->foo = $foo;
    }

    public function getFoo()
    {
        return $this->foo;
    }
}
