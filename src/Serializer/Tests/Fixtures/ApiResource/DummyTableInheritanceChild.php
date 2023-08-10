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

namespace ApiPlatform\Serializer\Tests\Fixtures\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource]
class DummyTableInheritanceChild extends DummyTableInheritance
{
    /**
     * @var string The dummy nickname
     */
    #[Groups(['default'])]
    private $nickname;

    public function getNickname()
    {
        return $this->nickname;
    }

    public function setNickname($nickname): void
    {
        $this->nickname = $nickname;
    }
}
