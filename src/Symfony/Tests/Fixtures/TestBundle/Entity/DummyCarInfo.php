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

namespace ApiPlatform\Symfony\Tests\Fixtures\TestBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @author Sergey Balasov <sbalasov@gmail.com>
 */
#[ORM\Embeddable]
class DummyCarInfo
{
    /**
     * @var string
     */
    #[ORM\Column(nullable: true)]
    public $name;
}
