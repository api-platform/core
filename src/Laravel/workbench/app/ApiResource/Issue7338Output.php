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

namespace Workbench\App\ApiResource;

use ApiPlatform\Metadata\ApiProperty;
use Symfony\Component\Serializer\Annotation\Groups;

class Issue7338Output
{
    #[ApiProperty(identifier: true)]
    public int $id;

    #[Groups(['issue7338:output:read'])]
    public string $name;

    public \DateTimeImmutable $date;

    public function __construct(int $id, string $name, \DateTimeImmutable $date)
    {
        $this->id = $id;
        $this->name = $name;
        $this->date = $date;
    }
}
