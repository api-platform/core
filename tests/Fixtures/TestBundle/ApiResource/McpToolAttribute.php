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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource;

use ApiPlatform\Metadata\McpTool;

#[McpTool(
    name: 'process_message',
    description: 'Process a message with priority',
    processor: [McpToolAttribute::class, 'process']
)]
class McpToolAttribute
{
    public function __construct(
        private string $message,
        private int $priority = 1,
    ) {
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function setPriority(int $priority): void
    {
        $this->priority = $priority;
    }

    public static function process($data): mixed
    {
        $data->setMessage('Processed: '.$data->getMessage());
        $data->setPriority($data->getPriority() + 10);

        return $data;
    }
}
