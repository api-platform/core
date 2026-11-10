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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue8766;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;

#[ApiResource(
    shortName: 'Issue8766Report',
    operations: [new Get(uriTemplate: '/issue8766_reports/{id}', provider: [self::class, 'provide'])],
)]
final class Issue8766Report
{
    public int $id = 1;

    /** @var array{total: int, processed: int, failed?: int|null} */
    public array $totals;

    /** @var array{name: string, ...<string, int>} */
    public array $unsealed;

    /** @var array{int, string} */
    public array $tuple;

    public static function provide(): self
    {
        $report = new self();
        $report->totals = ['total' => 0, 'processed' => 0];
        $report->unsealed = ['name' => ''];
        $report->tuple = [0, ''];

        return $report;
    }
}
