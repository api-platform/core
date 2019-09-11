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

use GraphQL\Error\Error;
use GraphQL\Error\FormattedError;

/**
 * @expremintal
 *
 * @author Mahmood Bazdar <mahmood@bazdar.me>
 */
class ExceptionFormatterCallback implements ExceptionFormatterCallbackInterface
{
    private $exceptionFormatterFactory;

    public function __construct(ExceptionFormatterFactoryInterface $exceptionFormatterFactory)
    {
        $this->exceptionFormatterFactory = $exceptionFormatterFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(Error $error): array
    {
        $formatters = $this->exceptionFormatterFactory->getExceptionFormatters();
        usort($formatters, function (ExceptionFormatterInterface $a, ExceptionFormatterInterface $b) {
            if ($a->getPriority() == $b->getPriority()) {
                return 0;
            }

            return ($a->getPriority() > $b->getPriority()) ? -1 : 1;
        });
        /** @var ExceptionFormatterInterface $exceptionFormatter */
        foreach ($formatters as $exceptionFormatter) {
            if (null !== $error->getPrevious() && $exceptionFormatter->supports($error->getPrevious())) {
                return $exceptionFormatter->format($error);
            }
        }

        // falling back to default GraphQL error formatter
        return FormattedError::createFromException($error);
    }
}
