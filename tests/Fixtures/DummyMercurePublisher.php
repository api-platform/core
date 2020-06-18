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

namespace ApiPlatform\Core\Tests\Fixtures;

use Symfony\Component\Mercure\Update;

class DummyMercurePublisher
{
    private $updates = [];

    public function __invoke(Update $update): string
    {
        $this->updates[] = $update;

        return 'dummy';
    }

    /**
     * @return array<Update>
     */
    public function getUpdates(): array
    {
        return $this->updates;
    }
}
