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

namespace ApiPlatform\Core\GraphQl\Exception;

use ApiPlatform\Core\GraphQl\Type\Definition\TypeInterface;
use Psr\Container\ContainerInterface;

/**
 * Get the registered services corresponding to GraphQL exception formatters.
 *
 * @author Mahmood Bazdar <mahmood@bazdar.me>
 */
class ExceptionFormatterFactory implements ExceptionFormatterFactoryInterface
{
    private $exceptionFormatterLocator;
    private $exceptionFormatterIds;

    /**
     * @param string[] $exceptionFormatterIds
     */
    public function __construct(ContainerInterface $exceptionFormatterLocator, array $exceptionFormatterIds)
    {
        $this->exceptionFormatterLocator = $exceptionFormatterLocator;
        $this->exceptionFormatterIds = $exceptionFormatterIds;
    }

    public function getExceptionFormatters(): array
    {
        $exceptionFormatters = [];

        foreach ($this->exceptionFormatterIds as $exceptionFormatterId) {
            /** @var TypeInterface $exceptionFormatter */
            $exceptionFormatter = $this->exceptionFormatterLocator->get($exceptionFormatterId);
            $exceptionFormatters[$exceptionFormatterId] = $exceptionFormatter;
        }

        return $exceptionFormatters;
    }
}
