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

namespace ApiPlatform\Symfony\Messenger;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\Stamp\StampInterface;

/**
 * An envelope stamp with context which related to a message.
 *
 * @author Sergii Pavlenko <sergii.pavlenko.v@gmail.com>
 */
final class ContextStamp implements StampInterface
{
    private readonly array $context;

    public function __construct(array $context = [])
    {
        /* Symfony does not guarantee that the Request object is serializable */
        if (($request = ($context['request'] ?? null)) && $request instanceof Request) {
            unset($context['request']);
        }

        $this->context = $context;
    }

    /**
     * Get the context related to a message.
     */
    public function getContext(): array
    {
        return $this->context;
    }
}
