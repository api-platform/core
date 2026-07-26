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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\DiscriminatedInputDto;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;

#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/discriminated_notification_channels',
            input: NotificationChannelInput::class,
            processor: [self::class, 'process'],
        ),
    ]
)]
final class ChannelResource
{
    public ?int $id = 1;

    public ?string $type = null;

    public ?string $url = null;

    public ?int $retries = null;

    public static function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): self
    {
        if (!$data instanceof WebhookChannelInput) {
            throw new \InvalidArgumentException(\sprintf('Expected "%s", got "%s".', WebhookChannelInput::class, get_debug_type($data)));
        }

        $resource = new self();
        $resource->type = $data->type;
        $resource->url = $data->config->url;
        $resource->retries = $data->config->retries;

        return $resource;
    }
}
