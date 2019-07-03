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

namespace ApiPlatform\Core\Swagger\SchemaFormatter;

use ApiPlatform\Core\Exception\FormatterNotFoundException;

final class ChainSchemaFormatter implements SchemaFormatterInterface
{
    private $formatters;

    /**
     * SchemaFormatterProvider constructor.
     *
     * @param DefinititionNormalizerInterface[] $formatters
     */
    public function __construct(/* iterable */ $formatters)
    {
        $this->formatters = $formatters;
    }

    public function getFormatter(string $mimeType): DefinititionNormalizerInterface
    {
        foreach ($this->formatters as $formatter) {
            if ($formatter->supports($mimeType)) {
                return $formatter;
            }
        }

        throw new FormatterNotFoundException(
            sprintf('No formatter supporting the "%s" MIME type is available.', $mimeType)
        );
    }
}
