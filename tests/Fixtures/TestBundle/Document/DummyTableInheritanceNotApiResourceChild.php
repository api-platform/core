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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document
 */
class DummyTableInheritanceNotApiResourceChild extends DummyTableInheritance
{
    /**
     * @var bool The dummy swagg
     *
     * @ODM\Field(type="boolean")
     */
    private $swaggerThanParent;

    public function __construct()
    {
        // Definitely always swagger than parents
        $this->swaggerThanParent = true;
    }

    public function isSwaggerThanParent(): bool
    {
        return $this->swaggerThanParent;
    }

    public function setSwaggerThanParent(bool $swaggerThanParent)
    {
        $this->swaggerThanParent = $swaggerThanParent;
    }
}
