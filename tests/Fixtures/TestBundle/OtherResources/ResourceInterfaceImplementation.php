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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\OtherResources;

/**
 * @author Maxime Veber <maxime.veber@nekland.fr>
 */
class ResourceInterfaceImplementation implements ResourceInterface
{
    /**
     * @var string
     */
    private $foo;

    public function setFoo(string $foo)
    {
        $this->foo = $foo;

        return $this;
    }

    public function getFoo(): string
    {
        return $this->foo;
    }
}
