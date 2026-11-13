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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue8765;

use ApiPlatform\Metadata\ApiProperty;

final readonly class Issue8765AuthorOutput
{
    /**
     * @param list<Issue8765AwardOutput> $awards
     */
    public function __construct(
        public string $name,
        #[ApiProperty(readableLink: true)]
        public Issue8765CountryOutput $country,
        #[ApiProperty(readableLink: true)]
        public array $awards,
    ) {
    }
}
