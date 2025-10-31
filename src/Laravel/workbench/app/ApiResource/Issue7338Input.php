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

use Symfony\Component\Serializer\Annotation\Groups;

class Issue7338Input
{
    public ?int $id = null;

    #[Groups(['issue7338:input:write'])]
    public ?string $title = null;

    public ?string $description = null;

    public function __construct(?string $title = null, ?string $description = null)
    {
        $this->title = $title;
        $this->description = $description;
    }
}
