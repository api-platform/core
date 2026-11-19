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
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;

#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/discriminated_notification_channels',
            input: NotificationChannelInput::class,
            processor: [self::class, 'process'],
        ),
        new Patch(
            uriTemplate: '/discriminated_notification_channels/{id}',
            input: NotificationChannelInput::class,
            output: NotificationChannelInput::class,
            provider: [self::class, 'provide'],
            processor: [self::class, 'processPatch'],
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

    public static function provide(Operation $operation, array $uriVariables = [], array $context = []): NotificationChannelInput
    {
        return new WebhookChannelPatchInput('webhook_patch', null);
    }

    public static function processPatch(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): self
    {
        if (!$data instanceof WebhookChannelPatchInput) {
            throw new \InvalidArgumentException(\sprintf('Expected "%s", got "%s".', WebhookChannelPatchInput::class, get_debug_type($data)));
        }

        $resource = new self();
        $resource->type = $data->getType();
        $resource->url = $data->getConfig()?->url;
        $resource->retries = $data->getConfig()?->retries;

        return $resource;
    }
}
