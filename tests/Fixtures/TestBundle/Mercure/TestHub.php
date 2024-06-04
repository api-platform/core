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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Mercure;

use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Jwt\TokenFactoryInterface;
use Symfony\Component\Mercure\Jwt\TokenProviderInterface;
use Symfony\Component\Mercure\Update;

final class TestHub implements HubInterface
{
    /**
     * @var Update[]
     */
    private array $updates = [];

    public function __construct(private readonly HubInterface $hub)
    {
    }

    /**
     * @return array<Update>
     */
    public function getUpdates(): array
    {
        return $this->updates;
    }

    public function getUrl(): string
    {
        return $this->hub->getUrl();
    }

    public function getPublicUrl(): string
    {
        return $this->hub->getPublicUrl();
    }

    public function getProvider(): TokenProviderInterface
    {
        return $this->hub->getProvider();
    }

    public function getFactory(): ?TokenFactoryInterface
    {
        return $this->hub->getFactory();
    }

    public function publish(Update $update): string
    {
        $this->updates[] = $update;

        return $this->hub->publish($update);
    }
}
