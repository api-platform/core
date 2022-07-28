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

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class DummyTableInheritanceNotApiResourceChild extends DummyTableInheritance
{
    /**
     * @var bool The dummy swagg
     */
    #[ORM\Column(type: 'boolean')]
    private bool $swaggerThanParent = true;

    public function __construct()
    {
    }

    public function isSwaggerThanParent(): bool
    {
        return $this->swaggerThanParent;
    }

    public function setSwaggerThanParent(bool $swaggerThanParent): void
    {
        $this->swaggerThanParent = $swaggerThanParent;
    }
}
