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

use Symfony\Component\Messenger\Stamp\StampInterface;

/**
 * An envelope stamp with context which related to a message.
 *
 * @author Sergii Pavlenko <sergii.pavlenko.v@gmail.com>
 */
final class ContextStamp implements StampInterface
{
    private $context;

    public function __construct(array $context = [])
    {
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

class_alias(ContextStamp::class, \ApiPlatform\Core\Bridge\Symfony\Messenger\ContextStamp::class);
