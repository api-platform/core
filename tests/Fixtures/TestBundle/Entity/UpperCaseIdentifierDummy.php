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

use ApiPlatform\Metadata\ApiResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * UpperCaseIdentifier dummy.
 *
 * @author Exploit.cz <insekticid@exploit.cz>
 */
#[ApiResource]
#[ORM\Entity]
class UpperCaseIdentifierDummy
{
    /**
     * @var string The custom identifier
     */
    #[ORM\Column(type: 'guid')]
    #[ORM\Id]
    private string $Uuid;
    /**
     * @var string The dummy name
     */
    #[ORM\Column(length: 30)]
    private string $name;

    public function getUuid(): string
    {
        return $this->Uuid;
    }

    public function setUuid(string $Uuid): void
    {
        $this->Uuid = $Uuid;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }
}
