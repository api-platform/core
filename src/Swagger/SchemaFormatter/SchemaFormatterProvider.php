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

class SchemaFormatterProvider
{
    /** @var SchemaFormatterInterface[] */
    private $formatters;

    public function __construct(/* iterable */ $formatters)
    {
        $this->formatters = $formatters;
    }

    /**
     * @param string $mimeType
     *
     * @return SchemaFormatterInterface
     */
    public function getFormatter($mimeType)
    {
        foreach ($this->formatters as $formatter) {
            if ($formatter->supports($mimeType)) {
                return $formatter;
            }
        }

        throw new FormatterNotFoundException(sprintf('Formatter for mimetype "%s" not supported', $mimeType));
    }
}
