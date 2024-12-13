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

namespace ApiPlatform\Tests;

trait WithResourcesTrait
{
    /**
     * @param class-string[] $resources
     */
    protected static function writeResources(array $resources): void
    {
        file_put_contents(__DIR__.'/Fixtures/app/var/resources.php', \sprintf('<?php return [%s];', implode(',', array_map(fn ($v) => $v.'::class', $resources))));
    }

    protected static function removeResources(): void
    {
        file_put_contents(__DIR__.'/Fixtures/app/var/resources.php', '<?php return [];');
    }
}
