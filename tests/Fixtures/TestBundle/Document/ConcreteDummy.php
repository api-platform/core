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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Document;

use ApiPlatform\Metadata\ApiResource;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Concrete Dummy.
 *
 * @author Jérémy Derusse <jeremy@derusse.com>
 */
#[ApiResource]
#[ODM\Document]
class ConcreteDummy extends AbstractDummy
{
    #[Assert\NotBlank]
    #[ODM\Field]
    private ?string $instance = null;

    public function setInstance($instance): void
    {
        $this->instance = $instance;
    }

    public function getInstance(): ?string
    {
        return $this->instance;
    }
}
