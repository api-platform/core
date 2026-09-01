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
use Symfony\Component\Mercure\ProtocolVersion;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Uid\Uuid;

final class TestHub implements HubInterface
{
    /**
     * @var Update[]
     */
    private array $updates = [];

    /**
     * Only the dedicated Mercure test env runs a hub; publishing anywhere else would call an
     * unrelated third party over the network and make the whole suite flaky.
     */
    public function __construct(private readonly HubInterface $hub, private readonly bool $publishToHub = false)
    {
    }

    /**
     * @return array<Update>
     */
    public function getUpdates(): array
    {
        return $this->updates;
    }

    // @TODO: remove in 4.3
    public function getUrl(): string
    {
        if (!method_exists($this->hub, 'getUrl')) {
            throw new \RuntimeException();
        }

        return $this->hub->getUrl();
    }

    public function getPublicUrl(): string
    {
        return $this->hub->getPublicUrl();
    }

    public function getProvider(): TokenProviderInterface
    {
        if (!method_exists($this->hub, 'getProvider')) {
            throw new \RuntimeException();
        }

        return $this->hub->getProvider();
    }

    public function getFactory(): ?TokenFactoryInterface
    {
        return $this->hub->getFactory();
    }

    public function publish(Update $update): string
    {
        $this->updates[] = $update;

        if (!$this->publishToHub) {
            return 'urn:uuid:'.Uuid::v4()->toRfc4122();
        }

        return $this->hub->publish($update);
    }

    // Added to HubInterface in symfony/mercure 0.8. ProtocolVersion does not exist on 0.6/0.7, but PHP
    // only resolves a return type when the method is called, and nothing calls these before 0.8.
    public function getProtocolVersion(): ProtocolVersion
    {
        return $this->hub->getProtocolVersion();
    }

    public function getCookieName(): string
    {
        return $this->hub->getCookieName();
    }
}
